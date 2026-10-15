param(
    [string] $OutputDir = "dist",
    [switch] $IncludeUploads,
    [switch] $IncludeDatabaseFiles
)

$ErrorActionPreference = "Stop"

$projectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$outputPath = Join-Path $projectRoot $OutputDir
$stamp = Get-Date -Format "yyyyMMdd-HHmmss"
$packageName = "unfaired-deploy-$stamp.zip"
$packagePath = Join-Path $outputPath $packageName
$stagingPath = Join-Path $outputPath "deploy-staging"

$excludeDirectories = @(
    ".git",
    ".agents",
    "dist",
    "migrations",
    "vendor",
    "node_modules"
)

$excludeFiles = @(
    ".env",
    ".env.local",
    "config/local.php",
    "database.sql",
    "error_log"
)

$excludeExtensions = @(
    ".log"
)

function Test-IsExcludedDirectory {
    param([string] $RelativePath)

    foreach ($directory in $excludeDirectories) {
        if ($directory -eq "migrations" -and $IncludeDatabaseFiles) {
            continue
        }

        if ($RelativePath -eq $directory -or $RelativePath.StartsWith("$directory\")) {
            return $true
        }
    }

    if (-not $IncludeUploads) {
        if ($RelativePath -eq "public\uploads\events" -or $RelativePath.StartsWith("public\uploads\events\")) {
            return $true
        }

        if ($RelativePath -eq "public\uploads\shop" -or $RelativePath.StartsWith("public\uploads\shop\")) {
            return $true
        }
    }

    return $false
}

function Test-IsExcludedFile {
    param([System.IO.FileInfo] $File, [string] $RelativePath)

    foreach ($fileName in $excludeFiles) {
        if ($fileName -eq "database.sql" -and $IncludeDatabaseFiles) {
            continue
        }

        if ($RelativePath -eq $fileName) {
            return $true
        }
    }

    foreach ($extension in $excludeExtensions) {
        if ($File.Extension -eq $extension) {
            return $true
        }
    }

    if (-not $IncludeUploads) {
        if (
            $RelativePath.StartsWith("public\uploads\events\") -and
            $File.Name -notin @(".gitkeep", ".htaccess")
        ) {
            return $true
        }

        if (
            $RelativePath.StartsWith("public\uploads\shop\") -and
            $File.Name -notin @(".gitkeep", ".htaccess")
        ) {
            return $true
        }
    }

    return $false
}

if (Test-Path $stagingPath) {
    Remove-Item -LiteralPath $stagingPath -Recurse -Force
}

New-Item -ItemType Directory -Path $outputPath -Force | Out-Null
New-Item -ItemType Directory -Path $stagingPath -Force | Out-Null

$rootLength = $projectRoot.Path.Length + 1

Get-ChildItem -LiteralPath $projectRoot -Force -Recurse | ForEach-Object {
    $relativePath = $_.FullName.Substring($rootLength)

    if ($_.PSIsContainer) {
        if (Test-IsExcludedDirectory -RelativePath $relativePath) {
            return
        }

        New-Item -ItemType Directory -Path (Join-Path $stagingPath $relativePath) -Force | Out-Null
        return
    }

    $parentRelativePath = Split-Path $relativePath -Parent
    if ($parentRelativePath -and (Test-IsExcludedDirectory -RelativePath $parentRelativePath)) {
        return
    }

    if (Test-IsExcludedFile -File $_ -RelativePath $relativePath) {
        return
    }

    $destination = Join-Path $stagingPath $relativePath
    $destinationDirectory = Split-Path $destination -Parent

    if ($destinationDirectory -and -not (Test-Path $destinationDirectory)) {
        New-Item -ItemType Directory -Path $destinationDirectory -Force | Out-Null
    }

    Copy-Item -LiteralPath $_.FullName -Destination $destination -Force
}

$manifest = @"
Unfaired Moto Club deploy package
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

Upload mode:
- Extract this ZIP in the domain document root if the server supports the included root .htaccess.
- The site will be served from public/ through rewrite rules.

Not included:
- .env and local env files
- .git and local agent files
- logs
- dist packages
- vendor/node_modules
- uploaded event/shop files, unless -IncludeUploads is used
- database.sql and migrations/, unless -IncludeDatabaseFiles is used

After upload:
1. Keep/create .env on the server with production DB credentials and APP_ENCRYPTION_KEY.
2. Do not delete public/uploads/ on the server.
3. Import SQL migrations only manually when the update explicitly requires it.
4. Test /api/membership-request.php. It must return JSON, not a WordPress 404 page.
"@

Set-Content -Path (Join-Path $stagingPath "DEPLOY_MANIFEST.txt") -Value $manifest -Encoding UTF8

if (Test-Path $packagePath) {
    Remove-Item -LiteralPath $packagePath -Force
}

Compress-Archive -Path (Join-Path $stagingPath "*") -DestinationPath $packagePath -Force
Remove-Item -LiteralPath $stagingPath -Recurse -Force

Write-Output "Deploy ZIP creato:"
Write-Output $packagePath
