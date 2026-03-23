#!/usr/bin/env bash
#
# build-usb-bundle.sh — Build an offline Windows Phoronix bundle zip
#
# Usage:
#   ./build-usb-bundle.sh [--no-video] [--output <path>]
#
# Produces: phoronix-bundle.zip (~9-15 GB with video files)
#
# Requirements (host): curl, zip, 7z (p7zip-full)
#
# --no-video  Skip the large video files (SVT-AV1 / x264 tests will fail)
# --output    Override output zip path (default: ./phoronix-bundle.zip)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CACHE_DIR="$SCRIPT_DIR/bundle-cache"
STAGE_DIR="$SCRIPT_DIR/bundle-stage"
OUTPUT_ZIP="$SCRIPT_DIR/phoronix-bundle.zip"
SKIP_VIDEO=0

# ─── Argument parsing ────────────────────────────────────────────────────────
while [[ $# -gt 0 ]]; do
  case "$1" in
    --no-video)  SKIP_VIDEO=1; shift ;;
    --output)    OUTPUT_ZIP="$2"; shift 2 ;;
    *) echo "Unknown argument: $1"; exit 1 ;;
  esac
done

# ─── Colour helpers ──────────────────────────────────────────────────────────
RED=$'\033[0;31m'; GREEN=$'\033[0;32m'; YELLOW=$'\033[1;33m'; BLUE=$'\033[0;34m'; NC=$'\033[0m'
info()    { echo "${BLUE}[INFO]${NC} $*"; }
ok()      { echo "${GREEN}[OK]${NC}   $*"; }
warn()    { echo "${YELLOW}[WARN]${NC} $*"; }
die()     { echo "${RED}[ERR]${NC}  $*" >&2; exit 1; }

# ─── Download helper (idempotent) ────────────────────────────────────────────
download() {
  local url="$1" dest_dir="$2" filename="${3:-}"
  [[ -z "$filename" ]] && filename="$(basename "$url")"
  local dest="$dest_dir/$filename"
  if [[ -f "$dest" ]]; then
    info "  Already cached: $filename"
    return 0
  fi
  info "  Downloading: $filename"
  mkdir -p "$dest_dir"
  # Try each URL in a comma-separated list
  IFS=',' read -ra urls <<< "$url"
  local ok_flag=0
  for u in "${urls[@]}"; do
    u="${u# }"   # trim leading space
    if curl -fsSL --retry 3 --retry-delay 5 -o "$dest" "$u"; then
      ok_flag=1
      break
    fi
  done
  if [[ $ok_flag -eq 0 ]]; then
    warn "  FAILED: $filename (tried all mirrors)"
    rm -f "$dest"
    return 1
  fi
}

# ─── Cygwin package download (from mirror, no Windows installer required) ────
download_cygwin_packages() {
  local pkg_dir="$CACHE_DIR/cygwin-packages"
  local mirror="https://mirrors.kernel.org/sourceware/cygwin"
  local arch="x86_64"
  local setup_ini="$pkg_dir/$arch/setup.ini"

  local packages=(bash unzip p7zip wget procps-ng bc which psmisc)

  mkdir -p "$pkg_dir/$arch"

  info "Fetching Cygwin setup.ini from mirror ..."
  curl -fsSL --retry 3 -o "$setup_ini" "$mirror/$arch/setup.ini" || \
    die "Could not fetch Cygwin setup.ini — check internet connectivity."

  # Copy cygwin-setup-x86_64.exe alongside packages (installer needs it)
  local setup_exe="$pkg_dir/cygwin-setup-x86_64.exe"
  if [[ ! -f "$setup_exe" ]]; then
    info "Downloading Cygwin setup.exe ..."
    curl -fsSL --retry 3 -o "$setup_exe" "https://cygwin.com/setup-x86_64.exe"
  fi

  # Parse setup.ini: extract install paths for each required package + deps
  # We use a two-pass approach: collect package names, then resolve recursively.
  local all_pkgs=()
  local -A visited=()

  resolve_deps() {
    local pkg="$1"
    [[ -n "${visited[$pkg]+x}" ]] && return
    visited["$pkg"]=1
    all_pkgs+=("$pkg")
    # Find "requires:" line for this package in setup.ini
    local requires
    requires=$(awk -v p="^@ $pkg$" '$0 ~ p {found=1} found && /^requires:/{print; exit}' "$setup_ini" | \
               sed 's/^requires: //' | tr ' ' '\n' | grep -v '^$' || true)
    for dep in $requires; do
      resolve_deps "$dep"
    done
  }

  for p in "${packages[@]}"; do
    resolve_deps "$p"
  done

  info "Cygwin packages to download: ${all_pkgs[*]}"

  # For each package, find the install: path in setup.ini and download it
  for pkg in "${all_pkgs[@]}"; do
    local install_line
    install_line=$(awk -v p="^@ $pkg$" '
      $0 ~ p { found=1 }
      found && /^\[curr\]/ { in_curr=1 }
      in_curr && /^install:/ { print; exit }
      /^$/ { found=0; in_curr=0 }
    ' "$setup_ini" || true)

    if [[ -z "$install_line" ]]; then
      # Fallback: find install: line directly after the @ block
      install_line=$(awk -v p="^@ $pkg$" '
        $0 ~ p { found=1; next }
        found && /^install:/ { print; exit }
        found && /^@/ { exit }
      ' "$setup_ini" || true)
    fi

    if [[ -z "$install_line" ]]; then
      warn "  Cannot find install line for Cygwin package: $pkg"
      continue
    fi

    # install line format: install: x86_64/release/pkg/pkg-version.tar.xz <size> <sha512>
    local rel_path
    rel_path=$(echo "$install_line" | awk '{print $2}')
    local pkg_url="$mirror/$rel_path"
    local pkg_dest_dir="$pkg_dir/$(dirname "$rel_path")"
    local pkg_file="$(basename "$rel_path")"

    mkdir -p "$pkg_dest_dir"
    if [[ -f "$pkg_dest_dir/$pkg_file" ]]; then
      info "  Cygwin cached: $pkg_file"
    else
      info "  Cygwin downloading: $pkg_file"
      curl -fsSL --retry 3 -o "$pkg_dest_dir/$pkg_file" "$pkg_url" || \
        warn "  FAILED: $pkg_file"
    fi
  done

  # Copy setup.ini into the arch subdir as expected by the offline installer
  ok "Cygwin packages downloaded to $pkg_dir"
}

# ─── Main ────────────────────────────────────────────────────────────────────
info "=== Phoronix USB Bundle Builder ==="
info "Cache dir : $CACHE_DIR"
info "Stage dir : $STAGE_DIR"
info "Output    : $OUTPUT_ZIP"
[[ $SKIP_VIDEO -eq 1 ]] && warn "Video files SKIPPED (--no-video)"

mkdir -p "$CACHE_DIR/deps" "$CACHE_DIR/pts-download-cache"

# ─── 1. Runtime dependencies ─────────────────────────────────────────────────
info "--- Step 1: Download runtime dependencies ---"
download "http://phoronix-test-suite.com/benchmark-files/php-8.2.1-Win32-vs16-x64.zip" \
  "$CACHE_DIR/deps"
download "https://aka.ms/vs/16/release/VC_redist.x64.exe" \
  "$CACHE_DIR/deps"

# ─── 2. Cygwin packages ──────────────────────────────────────────────────────
info "--- Step 2: Download Cygwin packages ---"
if [[ -d "$CACHE_DIR/cygwin-packages/x86_64/release" ]] && \
   [[ "$(find "$CACHE_DIR/cygwin-packages" -name "*.tar.xz" 2>/dev/null | wc -l)" -gt 5 ]]; then
  info "  Cygwin packages appear cached, skipping re-download."
else
  download_cygwin_packages
fi

# ─── 3. Test assets (Windows-only) ───────────────────────────────────────────
info "--- Step 3: Download Windows-specific test binaries ---"
DL="$CACHE_DIR/pts-download-cache"

download "https://installer.maxon.net/cinebench/CinebenchR23.zip" \
  "$DL" "CinebenchR23.zip"
download "https://www.7-zip.org/a/7z2500-extra.7z" \
  "$DL" "7z2500-extra.7z"
download "https://github.com/IndySockets/OpenSSL-Binaries/raw/master/Archive/openssl-1.0.1g-x64_86-win64.zip" \
  "$DL" "openssl-1.0.1g-x64_86-win64.zip"
download "https://download.gimp.org/mirror/pub/gimp/v2.10/windows/gimp-2.10.28-setup.exe" \
  "$DL" "gimp-2.10.28-setup.exe"
download "https://sqlite.org/2025/sqlite-tools-win-x64-3500400.zip" \
  "$DL" "sqlite-tools-win-x64-3500400.zip"
download "http://www.phoronix-test-suite.com/benchmark-files/SVT-AV1-v4.0.0-win64.zip" \
  "$DL" "SVT-AV1-v4.0.0-win64.zip"
download "https://phoenixnap.dl.sourceforge.net/project/libjpeg-turbo/2.1.0/libjpeg-turbo-2.1.0-vc64.exe,https://versaweb.dl.sourceforge.net/project/libjpeg-turbo/2.1.0/libjpeg-turbo-2.1.0-vc64.exe,https://netcologne.dl.sourceforge.net/project/libjpeg-turbo/2.1.0/libjpeg-turbo-2.1.0-vc64.exe" \
  "$DL" "libjpeg-turbo-2.1.0-vc64.exe"
download "http://assets.unigine.com/d/Unigine_Heaven-4.0.exe" \
  "$DL" "Unigine_Heaven-4.0.exe"
download "https://storage.googleapis.com/downloads.webmproject.org/releases/webp/libwebp-1.4.0-windows-x64.zip" \
  "$DL" "libwebp-1.4.0-windows-x64.zip"
download "https://artifacts.videolan.org/x264/release-win64/x264-r3094-bfc87b7.exe" \
  "$DL" "x264-r3094-bfc87b7.exe"
download "https://github.com/microsoft/diskspd/releases/download/v2.2/DiskSpd.zip" \
  "$DL" "DiskSpd-2.2.0.zip"

# ─── 4. Test assets (platform-agnostic) ──────────────────────────────────────
info "--- Step 4: Download platform-agnostic test assets ---"
download "http://www.phoronix-test-suite.com/benchmark-files/smallpt-1.tar.gz,http://www.phoronix.net/downloads/phoronix-test-suite/benchmark-files/smallpt-1.tar.gz" \
  "$DL" "smallpt-1.tar.gz"
download "http://www.phoronix-test-suite.com/benchmark-files/pts-sample-photos-2.tar.bz2,http://www.phoronix.net/downloads/phoronix-test-suite/benchmark-files/pts-sample-photos-2.tar.bz2" \
  "$DL" "pts-sample-photos-2.tar.bz2"
download "http://www.phoronix-test-suite.com/benchmark-files/stock-photos-jpeg-2018-1.tar.xz" \
  "$DL" "stock-photos-jpeg-2018-1.tar.xz"
download "http://www.phoronix-test-suite.com/benchmark-files/pts-sqlite-tests-1.tar.gz" \
  "$DL" "pts-sqlite-tests-1.tar.gz"
download "https://sqlite.org/2025/sqlite-autoconf-3500400.tar.gz" \
  "$DL" "sqlite-autoconf-3500400.tar.gz"
download "http://phoronix-test-suite.com/benchmark-files/ocr-image-samples-1.zip" \
  "$DL" "ocr-image-samples-1.zip"
download "http://phoronix-test-suite.com/benchmark-files/jpeg-test-1.zip" \
  "$DL" "jpeg-test-1.zip"
download "http://phoronix-test-suite.com/benchmark-files/sample-photo-6000x4000-1.zip" \
  "$DL" "sample-photo-6000x4000-1.zip"
download "http://www.phoronix-test-suite.com/benchmark-files/x264-git-20220222.tar.bz2" \
  "$DL" "x264-git-20220222.tar.bz2"

# ─── 5. Large video files ─────────────────────────────────────────────────────
if [[ $SKIP_VIDEO -eq 0 ]]; then
  info "--- Step 5: Download large video test files ---"
  download "http://ultravideo.cs.tut.fi/video/Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z" \
    "$DL" "Bosphorus_1920x1080_120fps_420_8bit_YUV_RAW.7z"
  download "http://ultravideo.cs.tut.fi/video/Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z" \
    "$DL" "Bosphorus_1920x1080_120fps_420_8bit_YUV_Y4M.7z"
  download "http://ultravideo.cs.tut.fi/video/Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z" \
    "$DL" "Bosphorus_3840x2160_120fps_420_8bit_YUV_Y4M.7z"
  download "http://ultravideo.fi/video/Beauty_3840x2160_120fps_420_10bit_YUV_RAW.7z" \
    "$DL" "Beauty_3840x2160_120fps_420_10bit_YUV_RAW.7z"
else
  warn "Skipping video files. SVT-AV1 and x264 tests will fail inside the VM."
fi

# ─── 6. Check existing PTS download cache for already-downloaded files ────────
info "--- Step 6: Copying already-cached PTS downloads from host ---"
HOST_CACHE="$HOME/.phoronix-test-suite/download-cache"
if [[ -d "$HOST_CACHE" ]]; then
  while IFS= read -r -d '' f; do
    fname="$(basename "$f")"
    dest="$DL/$fname"
    if [[ ! -f "$dest" ]]; then
      info "  Copying from host PTS cache: $fname"
      cp "$f" "$dest"
    fi
  done < <(find "$HOST_CACHE" -maxdepth 1 -type f -print0)
  ok "Host PTS cache checked."
fi

# ─── 7. Assemble bundle-stage ─────────────────────────────────────────────────
info "--- Step 7: Assembling bundle-stage ---"
rm -rf "$STAGE_DIR"
mkdir -p "$STAGE_DIR/deps"

# Copy install script
cp "$SCRIPT_DIR/install.ps1" "$STAGE_DIR/"

# Copy PTS source (exclude .git, bundle-cache, bundle-stage, output zip)
rsync -a --exclude='.git' \
         --exclude='bundle-cache' \
         --exclude='bundle-stage' \
         --exclude='phoronix-bundle*.zip' \
         "$SCRIPT_DIR/" "$STAGE_DIR/phoronix-test-suite/"

# Runtime deps
cp "$CACHE_DIR/deps/php-8.2.1-Win32-vs16-x64.zip" "$STAGE_DIR/deps/"
cp "$CACHE_DIR/deps/VC_redist.x64.exe" "$STAGE_DIR/deps/"

# Cygwin
cp "$CACHE_DIR/cygwin-packages/cygwin-setup-x86_64.exe" "$STAGE_DIR/deps/"
rsync -a "$CACHE_DIR/cygwin-packages/x86_64" "$STAGE_DIR/deps/cygwin-packages/"

# Test download cache
rsync -a "$CACHE_DIR/pts-download-cache/" "$STAGE_DIR/deps/pts-download-cache/"

ok "Bundle stage assembled at $STAGE_DIR"

# ─── 8. Create zip ────────────────────────────────────────────────────────────
info "--- Step 8: Creating bundle zip ---"
rm -f "$OUTPUT_ZIP"
# Use phoronix-bundle/ as the root dir inside the zip so
# Expand-Archive puts everything in C:\phoronix-bundle\
(cd "$STAGE_DIR" && zip -r "$OUTPUT_ZIP" . -x "*.DS_Store" -x "__MACOSX/*")

SIZE=$(du -sh "$OUTPUT_ZIP" | cut -f1)
SHA256=$(sha256sum "$OUTPUT_ZIP" | awk '{print $1}')
ok "=== Bundle created: $OUTPUT_ZIP ($SIZE) ==="
echo "SHA256: $SHA256"
echo ""
echo "Deploy with:"
echo "  ./host-scripts/guestcontrol-install.sh <vm-name> $OUTPUT_ZIP"
