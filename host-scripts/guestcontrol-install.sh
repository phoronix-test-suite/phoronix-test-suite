#!/usr/bin/env bash
#
# guestcontrol-install.sh — Deploy the phoronix-bundle.zip into a Windows VM
#                            via VBoxManage guestcontrol (no SSH, no internet)
#
# Usage:
#   ./host-scripts/guestcontrol-install.sh <vm-name> <bundle-zip-path> [options]
#
# Options:
#   --username USER   Windows guest username (required, or set GUEST_USER env var)
#   --password PASS   Windows guest password (required, or set GUEST_PASS env var)
#   --log-dest PATH   Local path to write the install log (default: ./phoronix-install.log)
#
# Requirements (host): VBoxManage in PATH, the VM must be running with VirtualBox
#                       Guest Additions installed.

set -euo pipefail

# ─── Defaults ────────────────────────────────────────────────────────────────
GUEST_USER="${GUEST_USER:-}"
GUEST_PASS="${GUEST_PASS:-}"
LOG_DEST="./phoronix-install.log"

# ─── Argument parsing ────────────────────────────────────────────────────────
if [[ $# -lt 2 ]]; then
  echo "Usage: $0 <vm-name> <bundle-zip-path> [--username USER] [--password PASS] [--log-dest PATH]"
  exit 1
fi

VM_NAME="$1"
BUNDLE_ZIP="$2"
shift 2

while [[ $# -gt 0 ]]; do
  case "$1" in
    --username) GUEST_USER="$2"; shift 2 ;;
    --password) GUEST_PASS="$2"; shift 2 ;;
    --log-dest) LOG_DEST="$2"; shift 2 ;;
    *) echo "Unknown argument: $1"; exit 1 ;;
  esac
done

# ─── Colour helpers ──────────────────────────────────────────────────────────
RED=$'\033[0;31m'; GREEN=$'\033[0;32m'; YELLOW=$'\033[1;33m'; BLUE=$'\033[0;34m'; NC=$'\033[0m'
info()    { echo "${BLUE}[INFO]${NC} $*"; }
ok()      { echo "${GREEN}[OK]${NC}   $*"; }
warn()    { echo "${YELLOW}[WARN]${NC} $*"; }
die()     { echo "${RED}[ERR]${NC}  $*" >&2; exit 1; }

# ─── Sanity checks ───────────────────────────────────────────────────────────
[[ -n "$GUEST_USER" ]] || die "Guest username required: use --username USER or set GUEST_USER env var"
[[ -n "$GUEST_PASS" ]] || die "Guest password required: use --password PASS or set GUEST_PASS env var"
[[ -f "$BUNDLE_ZIP" ]] || die "Bundle zip not found: $BUNDLE_ZIP"
command -v VBoxManage >/dev/null 2>&1 || die "VBoxManage not found in PATH"

# Shared guestcontrol flags
GC_FLAGS=(--username "$GUEST_USER" --password "$GUEST_PASS")

run_guest() {
  # Run a PowerShell command inside the guest, wait for it to finish
  # $1 = description, rest = powershell arguments
  local desc="$1"; shift
  info "$desc"
  VBoxManage guestcontrol "$VM_NAME" run \
    "${GC_FLAGS[@]}" \
    --wait-stdout --wait-stderr \
    --exe "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe" \
    -- powershell -NonInteractive -ExecutionPolicy Bypass "$@"
}

encode_ps() {
  # Encode a PowerShell command as base64/UTF-16LE for safe guestcontrol delivery
  printf '%s' "$1" | iconv -t UTF-16LE | base64 -w 0
}

# ─── Step 1: Copy bundle zip to the guest ────────────────────────────────────
GUEST_ZIP='C:\phoronix-bundle.zip'
info "Copying $(du -sh "$BUNDLE_ZIP" | cut -f1) bundle to VM (this may take a few minutes) ..."
VBoxManage guestcontrol "$VM_NAME" copyto \
  "${GC_FLAGS[@]}" \
  --source "$BUNDLE_ZIP" \
  --target "$GUEST_ZIP"
ok "Bundle copied to $GUEST_ZIP"

# ─── Step 2: Expand-Archive ──────────────────────────────────────────────────
PS_UNZIP="Expand-Archive -Path 'C:\\phoronix-bundle.zip' -DestinationPath 'C:\\' -Force"
ENCODED_UNZIP="$(encode_ps "$PS_UNZIP")"
info "Extracting bundle inside VM ..."
VBoxManage guestcontrol "$VM_NAME" run \
  "${GC_FLAGS[@]}" \
  --wait-stdout --wait-stderr \
  --exe "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe" \
  -- powershell -NonInteractive -EncodedCommand "$ENCODED_UNZIP"
ok "Bundle extracted to C:\\phoronix-bundle\\"

# ─── Step 3: Run install.ps1 ─────────────────────────────────────────────────
info "Running install.ps1 inside VM (this may take 10-30 minutes) ..."
VBoxManage guestcontrol "$VM_NAME" run \
  "${GC_FLAGS[@]}" \
  --wait-stdout --wait-stderr \
  --exe "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe" \
  -- powershell -NonInteractive -ExecutionPolicy Bypass \
     -File "C:\\phoronix-bundle\\install.ps1"
INSTALL_EXIT=$?

# ─── Step 4: Retrieve install log ────────────────────────────────────────────
info "Retrieving install log from VM ..."
VBoxManage guestcontrol "$VM_NAME" copyfrom \
  "${GC_FLAGS[@]}" \
  --source "C:\\phoronix-install.log" \
  --target "$LOG_DEST" 2>/dev/null || warn "Could not retrieve install log (install may have failed early)"

if [[ $INSTALL_EXIT -ne 0 ]]; then
  die "install.ps1 exited with code $INSTALL_EXIT. See log: $LOG_DEST"
fi

ok "=== Phoronix bundle installed successfully ==="
echo ""
echo "Run benchmarks from the guest:"
echo "  C:\\phoronix-test-suite\\phoronix-test-suite.bat batch-run local/firmwareci"
echo "  C:\\phoronix-test-suite\\phoronix-test-suite.bat batch-run local/firmwareci-3d-acceleration"
echo ""
echo "Collect results from:"
echo "  %USERPROFILE%\\.phoronix-test-suite\\test-results\\"
