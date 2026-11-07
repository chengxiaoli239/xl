#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 || ! -f "$1" ]]; then
  printf 'Usage: AWS_S3_CLIENT_BUCKET=... AWS_S3_CLIENT_BASE_URL=https://... %s /path/to/Lucky5-<platform>.zip\n' "$0" >&2
  exit 2
fi

for tool in aws curl jq shasum unzip; do
  command -v "$tool" >/dev/null || { printf '%s is required.\n' "$tool" >&2; exit 1; }
done

repo_root="$(cd "$(dirname "$0")/.." && pwd)"
package="$1"
manifest="${package%.zip}-package.json"
if [[ ! -f "$manifest" ]]; then
  manifest="$(dirname "$package")/Lucky5-package.json"
fi
bucket="${AWS_S3_CLIENT_BUCKET:-}"
region="${AWS_S3_CLIENT_REGION:-ap-east-1}"
base_url="${AWS_S3_CLIENT_BASE_URL:-}"
source_commit="$(git -C "$repo_root" rev-parse HEAD)"

if [[ ! "$bucket" =~ ^[A-Za-z0-9.-]+$ || ! "$region" =~ ^[a-z0-9-]+$ ||
      ! "$base_url" =~ ^https://[A-Za-z0-9./:_-]+$ || ! -f "$manifest" ]]; then
  printf 'Missing or invalid S3 configuration or package manifest.\n' >&2
  exit 2
fi

read -r platform architecture built_commit built_file built_size built_sha < <(
  jq -r '[.platform, (.architecture // "none"), .source_commit, .file, (.size | tostring), .sha256] | @tsv' "$manifest"
)
size="$(wc -c < "$package" | tr -d ' ')"
checksum="$(shasum -a 256 "$package" | awk '{print $1}')"
if [[ "$built_commit" != "$source_commit" || "$built_file" != "$(basename "$package")" ||
      "$built_size" != "$size" || "$built_sha" != "$checksum" ]]; then
  printf 'Package manifest does not match source or ZIP.\n' >&2
  exit 1
fi

case "$platform" in
  windows)
    if [[ "$architecture" != 'none' ]]; then
      printf 'Windows package must not declare a macOS architecture.\n' >&2
      exit 1
    fi
    payload='Lucky5/Lucky5.exe'
    prefix='lottery_xl/windows'
    release_suffix=''
    ;;
  macos)
    payload='Lucky5/Lucky5.app/Contents/Info.plist'
    prefix='lottery_xl/macos'
    case "$architecture" in
      arm64|x86_64) release_suffix="-${architecture}" ;;
      none) release_suffix='' ;;
      *) printf 'Unsupported macOS architecture.\n' >&2; exit 1 ;;
    esac
    ;;
  *) printf 'Unsupported package platform.\n' >&2; exit 1 ;;
esac
if ! unzip -Z1 "$package" | grep -Fx "$payload" >/dev/null; then
  printf 'ZIP is missing expected payload: %s\n' "$payload" >&2
  exit 1
fi

version="$(date -u '+%Y%m%d-%H%M%S')-${source_commit:0:12}"
release_key="${prefix}/releases/Lucky5${release_suffix}-${version}.zip"
latest_key="${prefix}/Lucky5${release_suffix}-latest.zip"
metadata="sha256=${checksum},source-commit=${source_commit},version=${version}"
aws sts get-caller-identity --region "$region" --query Account --output text

aws s3 cp "$package" "s3://${bucket}/${release_key}" --region "$region" \
  --content-type 'application/zip' --cache-control 'public,max-age=31536000,immutable' \
  --metadata "$metadata" --only-show-errors
aws s3 cp "$package" "s3://${bucket}/${latest_key}" --region "$region" \
  --content-type 'application/zip' --cache-control 'no-cache,no-store,must-revalidate' \
  --metadata "$metadata" --only-show-errors

for key in "$release_key" "$latest_key"; do
  remote="$(aws s3api head-object --bucket "$bucket" --key "$key" --region "$region" \
    --query '[ContentLength,Metadata.sha256,Metadata."source-commit",Metadata.version]' --output text)"
  read -r remote_size remote_sha remote_commit remote_version <<< "$remote"
  if [[ "$remote_size" != "$size" || "$remote_sha" != "$checksum" ||
        "$remote_commit" != "$source_commit" || "$remote_version" != "$version" ]]; then
    printf 'S3 metadata mismatch: %s\n' "$key" >&2
    exit 1
  fi
done

download_copy="$(mktemp "${TMPDIR:-/tmp}/lucky5-package.XXXXXX")"
trap 'rm -f "$download_copy"' EXIT
curl --fail --location --silent --show-error --max-time 120 \
  "${base_url%/}/${latest_key}" --output "$download_copy"
if [[ "$(shasum -a 256 "$download_copy" | awk '{print $1}')" != "$checksum" ]]; then
  printf 'CloudFront package bytes do not match the local ZIP.\n' >&2
  exit 1
fi

printf 'Published: %s/%s\n' "${base_url%/}" "$latest_key"
printf 'Source commit: %s\nSHA-256: %s\n' "$source_commit" "$checksum"
