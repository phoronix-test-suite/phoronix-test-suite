#Requires -Version 5.1
<#
.SYNOPSIS
    Offline installer for Phoronix Test Suite on Windows.
    Runs inside the VM via VBoxManage guestcontrol — no internet or SSH required.

.DESCRIPTION
    Expects the zip to have been extracted to C:\phoronix-bundle\ (the default when
    Expand-Archive is run against phoronix-bundle.zip with -DestinationPath C:\).

    Directory layout expected inside the zip root (bundle-stage/):
        install.ps1                     <- this file
        phoronix-test-suite/            <- PTS source
        deps/
            php-8.2.1-Win32-vs16-x64.zip
            VC_redist.x64.exe
            cygwin-setup-x86_64.exe
            cygwin-packages/            <- offline Cygwin package cache
            pts-download-cache/         <- pre-downloaded test assets

.NOTES
    Exit codes:
        0  — success
        1  — fatal error (see C:\phoronix-install.log)
#>

$ErrorActionPreference = 'Stop'

# ---------------------------------------------------------------------------
# Paths
# ---------------------------------------------------------------------------
$BundleRoot  = Split-Path -Parent $MyInvocation.MyCommand.Path   # C:\phoronix-bundle
$DepsDir     = Join-Path $BundleRoot 'deps'
$PtsSrc      = Join-Path $BundleRoot 'phoronix-test-suite'
$PtsInstall  = 'C:\phoronix-test-suite'
$PhpDest     = 'C:\PHP'
$CygwinRoot  = 'C:\cygwin64'
$LogFile     = 'C:\phoronix-install.log'
$PtsUser     = Join-Path $env:USERPROFILE '.phoronix-test-suite'

# ---------------------------------------------------------------------------
# Logging
# ---------------------------------------------------------------------------
function Log {
    param([string]$Msg, [string]$Level = 'INFO')
    $ts = Get-Date -Format 'HH:mm:ss'
    $line = "[$ts][$Level] $Msg"
    Write-Host $line
    Add-Content -Path $LogFile -Value $line
}

function Die {
    param([string]$Msg)
    Log $Msg 'ERROR'
    exit 1
}

# Start fresh log
Set-Content -Path $LogFile -Value "=== Phoronix Bundle Install $(Get-Date) ==="

Log "BundleRoot : $BundleRoot"
Log "DepsDir    : $DepsDir"
Log "PtsInstall : $PtsInstall"

# ---------------------------------------------------------------------------
# Verify bundle layout
# ---------------------------------------------------------------------------
foreach ($required in @(
    (Join-Path $DepsDir 'php-8.2.1-Win32-vs16-x64.zip'),
    (Join-Path $DepsDir 'VC_redist.x64.exe'),
    (Join-Path $DepsDir 'cygwin-setup-x86_64.exe'),
    (Join-Path $DepsDir 'cygwin-packages'),
    (Join-Path $DepsDir 'pts-download-cache'),
    $PtsSrc
)) {
    if (-not (Test-Path $required)) { Die "Missing required bundle item: $required" }
}

# ---------------------------------------------------------------------------
# 1. PHP
# ---------------------------------------------------------------------------
Log "--- Step 1: Install PHP ---"
if (Test-Path (Join-Path $PhpDest 'php.exe')) {
    Log "PHP already installed at $PhpDest, skipping."
} else {
    $phpZip = Join-Path $DepsDir 'php-8.2.1-Win32-vs16-x64.zip'
    Log "Extracting PHP to $PhpDest ..."
    Expand-Archive -Path $phpZip -DestinationPath $PhpDest -Force
    if (-not (Test-Path (Join-Path $PhpDest 'php.exe'))) { Die "PHP extraction failed." }
    Log "PHP installed."
}

# ---------------------------------------------------------------------------
# 2. Visual C++ Redistributable
# ---------------------------------------------------------------------------
Log "--- Step 2: Install Visual C++ Redistributable ---"
$vcDest = Join-Path $DepsDir 'VC_redist.x64.exe'
Log "Running VC++ installer (silent) ..."
$proc = Start-Process -FilePath $vcDest -ArgumentList '/install', '/quiet', '/norestart' -Wait -PassThru
if ($proc.ExitCode -notin @(0, 1638, 3010)) {
    # 1638 = already installed (newer version present), 3010 = reboot required but installed
    Die "VC++ Redistributable installer exited with code $($proc.ExitCode)"
}
Log "VC++ Redistributable installed (exit $($proc.ExitCode))."

# ---------------------------------------------------------------------------
# 3. Cygwin (offline install)
# ---------------------------------------------------------------------------
Log "--- Step 3: Install Cygwin (offline) ---"
$cygSetup   = Join-Path $DepsDir 'cygwin-setup-x86_64.exe'
$cygPkgDir  = Join-Path $DepsDir 'cygwin-packages'
$cygPackages = 'bash,unzip,p7zip,wget,procps-ng,bc,which,psmisc'

if (Test-Path (Join-Path $CygwinRoot 'bin\bash.exe')) {
    Log "Cygwin already installed at $CygwinRoot, skipping."
} else {
    Log "Installing Cygwin from local package cache ..."
    $cygArgs = @(
        '--quiet-mode',
        '--local-install',
        '--local-package-dir', $cygPkgDir,
        '--root', $CygwinRoot,
        '--packages', $cygPackages,
        '--no-admin'
    )
    $proc = Start-Process -FilePath $cygSetup -ArgumentList $cygArgs -Wait -PassThru
    if ($proc.ExitCode -ne 0) { Die "Cygwin install exited with code $($proc.ExitCode)" }
    if (-not (Test-Path (Join-Path $CygwinRoot 'bin\bash.exe'))) {
        Die "Cygwin install appeared to succeed but bash.exe not found."
    }
    Log "Cygwin installed."

    # Fix NTFS ACL (same as microsoft_dependency_handler.php does)
    $fstab = Join-Path $CygwinRoot 'etc\fstab'
    if (Test-Path $fstab) {
        (Get-Content $fstab) -replace 'acl', 'noacl' | Set-Content $fstab
        Log "Cygwin fstab patched (noacl)."
    }
}

# ---------------------------------------------------------------------------
# 4. Install Phoronix Test Suite
# ---------------------------------------------------------------------------
Log "--- Step 4: Install PTS to $PtsInstall ---"
if (Test-Path $PtsInstall) {
    Log "Removing existing PTS installation ..."
    Remove-Item -Recurse -Force $PtsInstall
}
Copy-Item -Path $PtsSrc -Destination $PtsInstall -Recurse -Force
Log "PTS installed."

# Place VC_redist.x64.exe inside the PTS dir so phoronix-test-suite.bat's
# "If not exist VC_redist.x64.exe" check finds it and skips re-download.
Copy-Item -Path $vcDest -Destination $PtsInstall -Force

# ---------------------------------------------------------------------------
# 5. Local test profiles → user profile directory
# ---------------------------------------------------------------------------
Log "--- Step 5: Install local test profiles ---"
$localProfilesSrc  = Join-Path $PtsInstall 'test-profiles\local'
$localProfilesDest = Join-Path $PtsUser 'test-profiles\local'
New-Item -ItemType Directory -Force -Path $localProfilesDest | Out-Null
Copy-Item -Path "$localProfilesSrc\*" -Destination $localProfilesDest -Recurse -Force
Log "Local test profiles installed."

# ---------------------------------------------------------------------------
# 6. Local test suites → user profile directory
# ---------------------------------------------------------------------------
Log "--- Step 6: Install local test suites ---"
$localSuitesSrc  = Join-Path $PtsInstall 'test-suites\local'
$localSuitesDest = Join-Path $PtsUser 'test-suites\local'
New-Item -ItemType Directory -Force -Path $localSuitesDest | Out-Null
Copy-Item -Path "$localSuitesSrc\*" -Destination $localSuitesDest -Recurse -Force
Log "Local test suites installed."

# ---------------------------------------------------------------------------
# 7. Populate PTS download cache
# ---------------------------------------------------------------------------
Log "--- Step 7: Populate PTS download cache ---"
$downloadCacheSrc  = Join-Path $DepsDir 'pts-download-cache'
$downloadCacheDest = Join-Path $PtsUser 'download-cache'
New-Item -ItemType Directory -Force -Path $downloadCacheDest | Out-Null
Get-ChildItem -Path $downloadCacheSrc -File | ForEach-Object {
    $dest = Join-Path $downloadCacheDest $_.Name
    if (-not (Test-Path $dest)) {
        Copy-Item -Path $_.FullName -Destination $dest -Force
        Log "  Cached: $($_.Name)"
    } else {
        Log "  Already cached: $($_.Name)"
    }
}
Log "Download cache populated."

# ---------------------------------------------------------------------------
# 8. Write batch-mode user config (no interactive prompts)
# ---------------------------------------------------------------------------
Log "--- Step 8: Write PTS user-config.xml (batch mode) ---"
New-Item -ItemType Directory -Force -Path $PtsUser | Out-Null
$userConfigXml = @'
<?xml version="1.0"?>
<PhoronixTestSuite>
  <Options>
    <OpenBenchmarking>
      <AnonymousUsageReporting>FALSE</AnonymousUsageReporting>
      <IndexCacheTTL>0</IndexCacheTTL>
      <AlwaysUploadSystemLogs>FALSE</AlwaysUploadSystemLogs>
    </OpenBenchmarking>
    <General>
      <DefaultBrowser></DefaultBrowser>
      <UsePhpCli>TRUE</UsePhpCli>
      <FullOutput>FALSE</FullOutput>
      <ColoredConsole>FALSE</ColoredConsole>
      <DefaultDisplayMode>DEFAULT</DefaultDisplayMode>
      <PhoromaticServers></PhoromaticServers>
      <PromptForTestIdentifier>FALSE</PromptForTestIdentifier>
      <PromptForTestDescription>FALSE</PromptForTestDescription>
      <PromptSaveResults>FALSE</PromptSaveResults>
      <AutoSaveResults>TRUE</AutoSaveResults>
      <Batch>TRUE</Batch>
    </General>
    <Modules></Modules>
    <Installation>
      <RemoveDownloadedFiles>FALSE</RemoveDownloadedFiles>
      <SearchMediaForCache>TRUE</SearchMediaForCache>
      <SymLinkFilesFromCache>FALSE</SymLinkFilesFromCache>
      <PromptInstallDependencies>FALSE</PromptInstallDependencies>
    </Installation>
  </Options>
</PhoronixTestSuite>
'@
Set-Content -Path (Join-Path $PtsUser 'user-config.xml') -Value $userConfigXml
Log "user-config.xml written."

# ---------------------------------------------------------------------------
# 9. Pre-install all test profiles (extract binaries from download cache)
# ---------------------------------------------------------------------------
Log "--- Step 9: Pre-install test profiles via PTS ---"
$phpBin = Join-Path $PhpDest 'php.exe'
$ptsPhp = Join-Path $PtsInstall 'pts-core\phoronix-test-suite.php'

# Add PHP and Cygwin to PATH for this session so install.sh scripts can find tools
$env:PATH = "$PhpDest;$CygwinRoot\bin;$($env:PATH)"
$env:PTS_SILENT_MODE = '1'

function Invoke-PTS {
    param([string[]]$Args)
    $argStr = $Args -join ' '
    Log "  Running: phoronix-test-suite $argStr"
    $proc = Start-Process -FilePath $phpBin `
        -ArgumentList (@("`"$ptsPhp`"") + $Args) `
        -Wait -PassThru -NoNewWindow `
        -RedirectStandardOutput "$LogFile.stdout" `
        -RedirectStandardError  "$LogFile.stderr"
    $stdout = Get-Content "$LogFile.stdout" -Raw -ErrorAction SilentlyContinue
    $stderr = Get-Content "$LogFile.stderr" -Raw -ErrorAction SilentlyContinue
    if ($stdout) { Add-Content $LogFile $stdout }
    if ($stderr) { Add-Content $LogFile $stderr }
    return $proc.ExitCode
}

# Install via suite names so PTS resolves all deps transitively
foreach ($suite in @('local/firmwareci', 'local/firmwareci-3d-acceleration')) {
    Log "Installing suite: $suite"
    $code = Invoke-PTS @('install', $suite)
    if ($code -ne 0) {
        Log "WARNING: PTS install of $suite exited $code — some tests may not be ready." 'WARN'
    } else {
        Log "Suite $suite installed."
    }
}

# ---------------------------------------------------------------------------
# Done
# ---------------------------------------------------------------------------
Log "=== Installation complete ==="
Log "Run benchmarks with:"
Log "  C:\phoronix-test-suite\phoronix-test-suite.bat batch-run local/firmwareci"
exit 0
