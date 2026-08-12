[CmdletBinding()]
param(
    [Parameter()]
    [string] $BaselineRef = 'HEAD',

    [Parameter()]
    [string] $OutputPath = ''
)

$ErrorActionPreference = 'Stop'
$repositoryRoot = (Resolve-Path -LiteralPath (Join-Path $PSScriptRoot '..')).Path
$manifestPath = Join-Path $PSScriptRoot 'manifest.txt'
$repositoryPrefix = $repositoryRoot.TrimEnd([IO.Path]::DirectorySeparatorChar) + [IO.Path]::DirectorySeparatorChar

Push-Location $repositoryRoot
try {
    & git rev-parse --verify "$BaselineRef`^{commit}" *> $null
    if ($LASTEXITCODE -ne 0) {
        throw "Baseline Git '$BaselineRef' tidak valid."
    }

    if (-not (Test-Path -LiteralPath $manifestPath -PathType Leaf)) {
        throw "Manifest deployment tidak ditemukan: $manifestPath"
    }

    $entries = Get-Content -LiteralPath $manifestPath |
        ForEach-Object { $_.Trim() } |
        Where-Object { $_ -and -not $_.StartsWith('#') }

    foreach ($entry in $entries) {
        if ($entry -match '(^|/)(tests|database/factories)(/|$)' -or $entry -match '(^|/)\.env($|\.)') {
            throw "Manifest memuat path yang dilarang untuk paket production: $entry"
        }
    }

    if ([string]::IsNullOrWhiteSpace($OutputPath)) {
        $artifactDirectory = Join-Path $PSScriptRoot 'artifacts'
        New-Item -ItemType Directory -Path $artifactDirectory -Force | Out-Null
        $OutputPath = Join-Path $artifactDirectory "km-engagement-$((Get-Date).ToString('yyyyMMdd-HHmmss')).zip"
    }

    $resolvedOutput = if ([IO.Path]::IsPathRooted($OutputPath)) {
        [IO.Path]::GetFullPath($OutputPath)
    }
    else {
        [IO.Path]::GetFullPath((Join-Path $repositoryRoot $OutputPath))
    }
    $outputDirectory = Split-Path -Parent $resolvedOutput
    New-Item -ItemType Directory -Path $outputDirectory -Force | Out-Null

    $temporaryRoot = Join-Path ([IO.Path]::GetTempPath()) ("fastware-km-deploy-" + [guid]::NewGuid().ToString('N'))
    $stageRoot = Join-Path $temporaryRoot 'package'
    New-Item -ItemType Directory -Path $stageRoot -Force | Out-Null

    try {
        foreach ($entry in $entries) {
            if ($entry -eq 'public/build/**') {
                $buildSource = Join-Path $repositoryRoot 'public/build'
                if (-not (Test-Path -LiteralPath $buildSource -PathType Container)) {
                    throw 'public/build tidak ditemukan. Jalankan npm.cmd run build terlebih dahulu.'
                }

                $publicTarget = Join-Path $stageRoot 'public'
                New-Item -ItemType Directory -Path $publicTarget -Force | Out-Null
                Copy-Item -LiteralPath $buildSource -Destination $publicTarget -Recurse -Force
                continue
            }

            $source = [IO.Path]::GetFullPath((Join-Path $repositoryRoot $entry))
            if (-not $source.StartsWith($repositoryPrefix, [StringComparison]::OrdinalIgnoreCase)) {
                throw "Path manifest keluar dari repository: $entry"
            }
            if (-not (Test-Path -LiteralPath $source -PathType Leaf)) {
                throw "File manifest tidak ditemukan: $entry"
            }

            $destination = Join-Path $stageRoot $entry
            $destinationDirectory = Split-Path -Parent $destination
            New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
            Copy-Item -LiteralPath $source -Destination $destination -Force
        }

        $baselineCommit = (& git rev-parse "$BaselineRef`^{commit}").Trim()
        @(
            "baseline_ref=$BaselineRef"
            "baseline_commit=$baselineCommit"
            "created_at=$((Get-Date).ToString('o'))"
        ) | Set-Content -LiteralPath (Join-Path $stageRoot 'DEPLOY-METADATA.txt') -Encoding utf8

        Compress-Archive -Path (Join-Path $stageRoot '*') -DestinationPath $resolvedOutput -Force
        Write-Output "Paket deployment dibuat: $resolvedOutput"
        Write-Output "Baseline: $BaselineRef ($baselineCommit)"
        Write-Output "Catatan: file delete legacy tetap harus dijalankan manual sesuai DEPLOY.md."
    }
    finally {
        if (Test-Path -LiteralPath $temporaryRoot -PathType Container) {
            $resolvedTemporary = (Resolve-Path -LiteralPath $temporaryRoot).Path
            $systemTemporary = [IO.Path]::GetFullPath([IO.Path]::GetTempPath())
            $temporaryLeaf = Split-Path -Leaf $resolvedTemporary
            if ($resolvedTemporary.StartsWith($systemTemporary, [StringComparison]::OrdinalIgnoreCase) -and
                $temporaryLeaf.StartsWith('fastware-km-deploy-', [StringComparison]::Ordinal)) {
                Remove-Item -LiteralPath $resolvedTemporary -Recurse -Force
            }
            else {
                throw "Temporary path tidak aman untuk dibersihkan: $resolvedTemporary"
            }
        }
    }
}
finally {
    Pop-Location
}
