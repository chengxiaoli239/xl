#!/usr/bin/env bash
set -euo pipefail

if [[ $# -ne 1 || ! -f "$1" ]]; then
  printf 'Usage: AWS_S3_CLIENT_BUCKET=... AWS_S3_CLIENT_BASE_URL=https://... %s /path/to/Lucky5.exe\n' "$0" >&2
  exit 2
fi

command -v aws >/dev/null || { printf 'AWS CLI is required.\n' >&2; exit 1; }
command -v curl >/dev/null || { printf 'curl is required.\n' >&2; exit 1; }

repo_root="$(cd "$(dirname "$0")/.." && pwd)"
source_commit="$(git -C "$repo_root" rev-parse HEAD)"
source_short="${source_commit:0:12}"
source_exe="$1"
bucket="${AWS_S3_CLIENT_BUCKET:-}"
region="${AWS_S3_CLIENT_REGION:-ap-east-1}"
prefix="${AWS_S3_CLIENT_PREFIX:-lottery_xl/windows}"
base_url="${AWS_S3_CLIENT_BASE_URL:-}"
prefix="${prefix#/}"
prefix="${prefix%/}"
base_url="${base_url%/}"

if [[ ! "$source_commit" =~ ^[0-9a-f]{40}$ || ! "$bucket" =~ ^[A-Za-z0-9.-]+$ ||
      ! "$region" =~ ^[a-z0-9-]+$ || ! "$prefix" =~ ^[A-Za-z0-9._/-]+$ ||
      "$prefix" == *'..'* || ! "$base_url" =~ ^https://[A-Za-z0-9./:_-]+$ ]]; then
  printf 'Invalid source commit or S3/CloudFront configuration.\n' >&2
  exit 2
fi

if [[ "$(od -An -tx1 -N2 "$source_exe" | tr -d ' \n')" != '4d5a' ]]; then
  printf 'The input is not a Windows PE executable (missing MZ header).\n' >&2
  exit 1
fi

checksum="$(shasum -a 256 "$source_exe" | awk '{print $1}')"
size="$(wc -c < "$source_exe" | tr -d ' ')"
version="$(date -u '+%Y%m%d-%H%M%S')-${source_short}"
release_key="${prefix}/releases/Lucky5-${version}.exe"
latest_key="${prefix}/Lucky5-latest.exe"
metadata="sha256=${checksum},source-commit=${source_commit},version=${version}"

aws sts get-caller-identity --region "$region" --query Arn --output text

aws s3 cp "$source_exe" "s3://${bucket}/${release_key}" --region "$region" \
  --content-type 'application/vnd.microsoft.portable-executable' \
  --cache-control 'public,max-age=31536000,immutable' --metadata "$metadata" --only-show-errors

aws s3 cp "$source_exe" "s3://${bucket}/${latest_key}" --region "$region" \
  --content-type 'application/vnd.microsoft.portable-executable' \
  --cache-control 'no-cache,no-store,must-revalidate' --metadata "$metadata" --only-show-errors

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

download_copy="$(mktemp "${TMPDIR:-/tmp}/lucky5-verify.XXXXXX")"
trap 'rm -f "$download_copy"' EXIT
curl --fail --location --silent --show-error --max-time 120 \
  "${base_url}/${latest_key}" --output "$download_copy"
download_sha="$(shasum -a 256 "$download_copy" | awk '{print $1}')"
if [[ "$download_sha" != "$checksum" ]]; then
  printf 'CloudFront download bytes do not match the Windows executable.\n' >&2
  exit 1
fi

printf 'Source commit: %s\n' "$source_commit"
printf 'Size: %s bytes\n' "$size"
printf 'SHA-256: %s\n' "$checksum"
printf 'S3 release: s3://%s/%s\n' "$bucket" "$release_key"
printf 'Verified download: %s/%s\n' "$base_url" "$latest_key"
