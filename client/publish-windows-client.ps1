$ErrorActionPreference = 'Stop'

function Require-Command([string]$Name) {
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Required command not found: $Name"
    }
}

Require-Command 'python'
Require-Command 'aws'
Require-Command 'git'

$repoRoot = (Resolve-Path (Join-Path $PSScriptRoot '..')).Path
$clientDir = Join-Path $repoRoot 'client'
$distDir = Join-Path $clientDir 'dist'
$exePath = Join-Path $distDir 'Lucky5.exe'
$buildScript = Join-Path $clientDir 'xy_client\build.py'

$bucket = $env:AWS_S3_CLIENT_BUCKET
$region = if ($env:AWS_S3_CLIENT_REGION) { $env:AWS_S3_CLIENT_REGION } else { 'ap-east-1' }
$prefix = if ($env:AWS_S3_CLIENT_PREFIX) { $env:AWS_S3_CLIENT_PREFIX.Trim('/') } else { 'windows' }
$baseUrl = if ($env:AWS_S3_CLIENT_BASE_URL) { $env:AWS_S3_CLIENT_BASE_URL.TrimEnd('/') } else { '' }

if ([string]::IsNullOrWhiteSpace($bucket)) {
    throw 'Set AWS_S3_CLIENT_BUCKET before publishing.'
}
if ($bucket -notmatch '^[A-Za-z0-9.-]+$' -or $region -notmatch '^[a-z0-9-]+$' -or
    $prefix -notmatch '^[A-Za-z0-9._/-]+$' -or $prefix.Contains('..')) {
    throw 'Invalid S3 release configuration.'
}

Push-Location $repoRoot
try {
    & python $buildScript --clean --name Lucky5
    if ($LASTEXITCODE -ne 0) { throw 'Lucky5 build failed.' }
    if (-not (Test-Path -LiteralPath $exePath -PathType Leaf)) {
        throw "Build output not found: $exePath"
    }

    $bytes = [System.IO.File]::ReadAllBytes($exePath)
    if ($bytes.Length -lt 2 -or $bytes[0] -ne 0x4d -or $bytes[1] -ne 0x5a) {
        throw 'Build output is not a Windows PE executable (missing MZ header).'
    }

    $hash = (Get-FileHash -LiteralPath $exePath -Algorithm SHA256).Hash.ToLowerInvariant()
    $size = (Get-Item -LiteralPath $exePath).Length
    $commit = (& git rev-parse HEAD).Trim()
    if ($commit -notmatch '^[0-9a-f]{40}$') { throw 'Unable to resolve a full Git source commit.' }
    $version = (Get-Date).ToUniversalTime().ToString('yyyyMMdd-HHmmss')
    $releaseKey = "$prefix/releases/Lucky5-$version.exe"
    $latestKey = "$prefix/Lucky5-latest.exe"
    $manifest = Join-Path ([System.IO.Path]::GetTempPath()) "Lucky5-$version.json"

    $manifestData = [ordered]@{
        product = 'Lucky5'
        version = $version
        source_commit = $commit
        size = [int64]$size
        sha256 = $hash
        published_at = (Get-Date).ToUniversalTime().ToString('o')
    }
    $manifestData | ConvertTo-Json | Set-Content -LiteralPath $manifest -Encoding UTF8

    & aws sts get-caller-identity --query Arn --output text
    if ($LASTEXITCODE -ne 0) { throw 'AWS credentials are unavailable.' }

    $metadata = "sha256=$hash,source-commit=$commit,version=$version"
    & aws s3 cp $exePath "s3://$bucket/$releaseKey" --region $region `
        --content-type 'application/vnd.microsoft.portable-executable' `
        --cache-control 'public,max-age=31536000,immutable' --metadata $metadata --only-show-errors
    if ($LASTEXITCODE -ne 0) { throw 'Versioned S3 upload failed.' }
    & aws s3 cp $exePath "s3://$bucket/$latestKey" --region $region `
        --content-type 'application/vnd.microsoft.portable-executable' `
        --cache-control 'no-cache,no-store,must-revalidate' --metadata $metadata --only-show-errors
    if ($LASTEXITCODE -ne 0) { throw 'Latest S3 upload failed.' }

    foreach ($key in @($releaseKey, $latestKey)) {
        $remote = & aws s3api head-object --bucket $bucket --key $key --region $region `
            --query '[ContentLength,Metadata.sha256,Metadata."source-commit",Metadata.version]' --output text
        if ($LASTEXITCODE -ne 0) { throw "S3 verification failed: $key" }
        $fields = ($remote -split "\s+")
        if ($fields[0] -ne "$size" -or $fields[1] -ne $hash -or $fields[2] -ne $commit -or $fields[3] -ne $version) {
            throw "S3 metadata mismatch: $key ($remote)"
        }
    }

    & aws s3 cp $manifest "s3://$bucket/$prefix/releases/Lucky5-$version.json" --region $region `
        --content-type 'application/json; charset=utf-8' --cache-control 'public,max-age=31536000,immutable' --only-show-errors
    if ($LASTEXITCODE -ne 0) { throw 'Release manifest upload failed.' }

    Write-Host "Published s3://$bucket/$releaseKey"
    Write-Host "Published s3://$bucket/$latestKey"
    Write-Host "Source commit: $commit"
    Write-Host "Size: $size bytes"
    Write-Host "SHA-256: $hash"
    if ($baseUrl) { Write-Host "Latest URL: $baseUrl/$latestKey" }
}
finally {
    Pop-Location
    if ($manifest -and (Test-Path -LiteralPath $manifest)) { Remove-Item -LiteralPath $manifest -Force }
}
