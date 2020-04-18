#!/bin/sh

tar -xvf zstd-1.3.4.tar.gz
cd zstd-1.3.4/
cat > portable-header-prefix.patch <<'EOF'
From edde62b003878b62b75492c2c446da1acb5d2b27 Mon Sep 17 00:00:00 2001
From: Ming Chen <ming.chen1986@gmail.com>
Date: Mon, 23 Mar 2020 10:33:17 +0800
Subject: [PATCH] compress-zstd: fix missing line breaks

This change fixes issue #132: compress-zstd-1.1.2: the embedded patch
fails to apply
---
 pts/compress-zstd-1.1.2/install.sh | 5 +++++
 1 file changed, 5 insertions(+)

diff --git a/pts/compress-zstd-1.1.2/install.sh b/pts/compress-zstd-1.1.2/install.sh
index af80c8c..518ba14 100644
--- a/pts/compress-zstd-1.1.2/install.sh
+++ b/pts/compress-zstd-1.1.2/install.sh
@@ -6,7 +6,9 @@ cat > portable-header-prefix.patch <<'EOF'
 --- programs/Makefile
 +++ programs/Makefile
 @@ -84,9 +84,14 @@ endif
+
  VOID = /dev/null
+
 +# more portable header prefix
 +# https://github.com/facebook/zstd/issues/1983
 +# https://github.com/facebook/zstd/commit/06a57cf57e3c4e887cadcf688e3081154f3f6db4
@@ -20,6 +22,7 @@ cat > portable-header-prefix.patch <<'EOF'
  ifeq ($(HAVE_THREAD), 1)
  THREAD_MSG := ==> building with threading support
 @@ -98,7 +103,7 @@ endif
+
  # zlib detection
  NO_ZLIB_MSG := ==> no zlib, building zstd without .gz support
 -HAVE_ZLIB := $(shell printf '\#include <zlib.h>\nint main(void) { return 0; }' | $(CC) $(FLAGS) -o have_zlib$(EXT) -x c - -lz 2> $(VOID) && rm have_zlib$(EXT) && echo 1 || echo 0)
@@ -28,6 +31,7 @@ cat > portable-header-prefix.patch <<'EOF'
  ZLIB_MSG := ==> building zstd with .gz compression support
  ZLIBCPP = -DZSTD_GZCOMPRESS -DZSTD_GZDECOMPRESS
 @@ -109,7 +114,7 @@ endif
+
  # lzma detection
  NO_LZMA_MSG := ==> no liblzma, building zstd without .xz/.lzma support
 -HAVE_LZMA := $(shell printf '\#include <lzma.h>\nint main(void) { return 0; }' | $(CC) $(FLAGS) -o have_lzma$(EXT) -x c - -llzma 2> $(VOID) && rm have_lzma$(EXT) && echo 1 || echo 0)
@@ -36,6 +40,7 @@ cat > portable-header-prefix.patch <<'EOF'
  LZMA_MSG := ==> building zstd with .xz/.lzma compression support
  LZMACPP = -DZSTD_LZMACOMPRESS -DZSTD_LZMADECOMPRESS
 @@ -120,7 +125,7 @@ endif
+
  # lz4 detection
  NO_LZ4_MSG := ==> no liblz4, building zstd without .lz4 support
 -HAVE_LZ4 := $(shell printf '\#include <lz4frame.h>\n\#include <lz4.h>\nint main(void) { return 0; }' | $(CC) $(FLAGS) -o have_lz4$(EXT) -x c - -llz4 2> $(VOID) && rm have_lz4$(EXT) && echo 1 || echo 0)
-- 
2.25.1


EOF
patch -p0 < portable-header-prefix.patch
make
cd ~
cat > compress-zstd <<EOT
#!/bin/sh
./zstd-1.3.4/zstd -19 -T\$NUM_CPU_CORES ubuntu-16.04.3-server-i386.img > /dev/null 2>&1
EOT
chmod +x compress-zstd
