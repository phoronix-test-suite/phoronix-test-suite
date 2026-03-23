# FirmwareCI — Offline Windows Benchmark Bundle

This repository is a fork of [Phoronix Test Suite](https://github.com/phoronix-test-suite/phoronix-test-suite)
extended with FirmwareCI-specific local test profiles, benchmark suites, and tooling to build and deploy
a fully self-contained offline bundle to Windows VMs — no internet access, no SSH required.

---

## Overview

The bundle is a ZIP file (~9–15 GB) that can be transferred to a Windows VM via USB stick or
`VBoxManage guestcontrol` and installed entirely offline. It includes:

- Phoronix Test Suite (PTS) PHP runtime
- All required runtime dependencies (PHP 8.2, VC++ Redistributable, Cygwin)
- All test profiles pre-configured for Windows
- All benchmark binary assets pre-downloaded

---

## Repository Layout

```
phoronix-test-suite/
├── test-profiles/local/          # FirmwareCI local test profiles (Windows-ready)
│   ├── cinebench-1.2.0/          # Cinebench R23 (CPU multi-core)
│   ├── compress-7zip-1.12.0/     # 7-Zip compression benchmark
│   ├── diskspd-2.2.0/            # Microsoft DiskSpd (CrystalDiskMark-equivalent)
│   ├── gimp-1.1.3/               # GIMP image processing
│   ├── openssl-1.9.3/            # OpenSSL crypto benchmark (Windows binary)
│   ├── smallpt-1.2.1/            # SmallPT global illumination renderer
│   ├── sqlite-2.3.0/             # SQLite multi-threaded benchmark
│   ├── svt-av1-2.17.0/           # SVT-AV1 video encoder
│   ├── tesseract-ocr-1.0.1/      # Tesseract OCR
│   ├── tjbench-1.2.0/            # libjpeg-turbo JPEG benchmark
│   ├── unigine-heaven-1.6.6/     # Unigine Heaven 3D graphics (requires GPU)
│   ├── webp-1.4.0/               # WebP image encoding
│   └── x264-2.7.0/               # x264 H.264 video encoder
├── test-suites/local/
│   ├── firmwareci/               # Main suite (no 3D acceleration required)
│   └── firmwareci-3d-acceleration/ # Extended suite including Unigine Heaven
├── install.ps1                   # Windows VM installer (entry point)
├── build-usb-bundle.sh           # Host-side bundle builder (run on Linux)
└── host-scripts/
    └── guestcontrol-install.sh   # VBoxManage guestcontrol deploy helper
```

---

## Benchmark Suites

### `local/firmwareci`

CPU, disk, and system benchmarks that run without 3D acceleration:

| Test | Description |
|------|-------------|
| SmallPT | Global illumination ray tracer (CPU bound) |
| OpenSSL | AES/RSA/SHA cryptographic throughput |
| x264 | H.264 encoding — Bosphorus 4K Y4M source |
| SVT-AV1 | AV1 encoding preset 8 — Bosphorus 4K Y4M source |
| 7-Zip | Compression/decompression benchmark |
| WebP | WebP image encoding |
| SQLite | Multi-threaded DB inserts — 1 / 8 / 32 threads |
| DiskSpd SEQ1MQ8T1 Read | Sequential 1M reads, queue depth 8 |
| DiskSpd SEQ1MQ1T1 Read | Sequential 1M reads, queue depth 1 |
| DiskSpd SEQ128KQ8T1 Read | Sequential 128K reads, queue depth 8 |
| DiskSpd RND4KQ32T1 Read | Random 4K reads, queue depth 32 |
| DiskSpd RND4KQ1T1 Read | Random 4K reads, queue depth 1 |
| GIMP | Unsharp mask / resize / rotate / auto-levels |
| Tesseract OCR | OCR throughput on image samples |
| libjpeg-turbo tjbench | JPEG encode/decode benchmark |
| Cinebench R23 | Multi-core CPU score |

### `local/firmwareci-3d-acceleration`

Same as `firmwareci` plus Unigine Heaven GPU benchmark. Requires VirtualBox 3D acceleration enabled.

---

## Workflow

### Step 1 — Build the bundle (on Linux host)

```bash
./build-usb-bundle.sh [--no-video] [--output <path>]
```

**What it does:**

1. Downloads all runtime dependencies to `bundle-cache/` (idempotent — skips cached files):
   - PHP 8.2.1 for Windows (`php-8.2.1-Win32-vs16-x64.zip`)
   - VC++ Redistributable 2019 x64 (`VC_redist.x64.exe`)
   - Cygwin setup.exe + offline package cache (bash, unzip, p7zip, wget, etc.)

2. Downloads all benchmark binary assets:
   - CinebenchR23.zip, 7z extra binaries, OpenSSL Windows binary
   - GIMP 2.10.28 installer, SQLite tools, SVT-AV1 Windows binaries
   - libjpeg-turbo, Unigine Heaven installer, libwebp Windows binaries
   - x264 pre-built Windows binary, DiskSpd

3. Downloads large video test files (skip with `--no-video`; saves ~7 GB):
   - Bosphorus 1080p RAW and Y4M (for x264/SVT-AV1)
   - Bosphorus 4K Y4M and Beauty 4K RAW (for 4K encoding tests)

4. Checks the host's own PTS download cache (`~/.phoronix-test-suite/download-cache/`) and
   copies any already-cached files into the bundle (avoids duplicate downloads).

5. Assembles `bundle-stage/` and zips it as `phoronix-bundle.zip`.

**Options:**

| Flag | Description |
| ---- | ----------- |
| `--no-video` | Skip the 7+ GB video files. SVT-AV1 and x264 tests will fail in the VM. |
| `--output <path>` | Override the output zip path (default: `./phoronix-bundle.zip`). |

**Requirements:** `curl`, `zip`, `rsync`, `iconv` (standard on most Linux distros).

**Bundle size:** ~9–15 GB with video files; ~2–3 GB with `--no-video`.

---

### Step 2a — Deploy via VBoxManage guestcontrol (automated)

```bash
./host-scripts/guestcontrol-install.sh <vm-name> <bundle-zip-path> [options]
```

**What it does:**

1. Copies the bundle ZIP to `C:\phoronix-bundle.zip` inside the VM via `guestcontrol copyto`.
2. Extracts it to `C:\` using PowerShell `Expand-Archive` (→ `C:\phoronix-bundle\`).
3. Runs `install.ps1` inside the VM and waits for completion.
4. Retrieves `C:\phoronix-install.log` back to the host.
5. Exits with the installer's exit code.

**Options:**

| Flag | Description |
| ---- | ----------- |
| `--username USER` | Windows guest username — required (or set `GUEST_USER` env var) |
| `--password PASS` | Windows guest password — required (or set `GUEST_PASS` env var) |
| `--log-dest PATH` | Where to save the retrieved install log (default: `./phoronix-install.log`) |

**Requirements:** VBoxManage in PATH; VM running with VirtualBox Guest Additions installed.

---

### Step 2b — Deploy via USB stick (manual)

1. Copy `phoronix-bundle.zip` to a USB stick.
2. On the Windows VM, copy the ZIP to `C:\phoronix-bundle.zip`.
3. Open PowerShell as Administrator and run:
   ```powershell
   Expand-Archive -Path C:\phoronix-bundle.zip -DestinationPath C:\ -Force
   Set-ExecutionPolicy Bypass -Scope Process -Force
   & C:\phoronix-bundle\install.ps1
   ```
4. Check `C:\phoronix-install.log` for results.

---

### Step 3 — Run benchmarks

After installation completes, run from a PowerShell or Command Prompt:

```bat
# Main suite (no 3D required)
C:\phoronix-test-suite\phoronix-test-suite.bat batch-run local/firmwareci

# Extended suite (requires 3D acceleration)
C:\phoronix-test-suite\phoronix-test-suite.bat batch-run local/firmwareci-3d-acceleration
```

Results are saved to `%USERPROFILE%\.phoronix-test-suite\test-results\`.

---

## What `install.ps1` Does

The installer runs fully unattended inside the VM. All output is logged to `C:\phoronix-install.log`.

| Step | Action |
|------|--------|
| 1 | Extract PHP 8.2.1 to `C:\PHP` |
| 2 | Install VC++ Redistributable 2019 silently |
| 3 | Install Cygwin from offline package cache to `C:\cygwin64` |
| 4 | Copy PTS to `C:\phoronix-test-suite\` |
| 5 | Copy local test profiles to `%USERPROFILE%\.phoronix-test-suite\test-profiles\local\` |
| 6 | Copy local test suites to `%USERPROFILE%\.phoronix-test-suite\test-suites\local\` |
| 7 | Copy all pre-downloaded test assets to `%USERPROFILE%\.phoronix-test-suite\download-cache\` |
| 8 | Write batch-mode `user-config.xml` (disables all interactive prompts) |
| 9 | Pre-install `local/firmwareci` and `local/firmwareci-3d-acceleration` via PHP directly |

The installer places `VC_redist.x64.exe` inside the PTS directory so `phoronix-test-suite.bat`
skips its own download check. PHP is pre-installed to `C:\PHP` for the same reason.
