#!/bin/sh

tar -xvf zstd-1.3.4.tar.gz
cd zstd-1.3.4/
cat > portable-header-prefix.patch <<'EOF'
--- programs/Makefile
+++ programs/Makefile
@@ -84,9 +84,14 @@ endif
 VOID = /dev/null
+# more portable header prefix
+# https://github.com/facebook/zstd/issues/1983
+# https://github.com/facebook/zstd/commit/06a57cf57e3c4e887cadcf688e3081154f3f6db4
+NUM_SYMBOL := \#
+
 # thread detection
 NO_THREAD_MSG := ==> no threads, building without multithreading support
-HAVE_PTHREAD := $(shell printf '\#include <pthread.h>\nint main(void) { return 0; }' | $(CC) $(FLAGS) -o have_pthread$(EXT) -x c - -pthread 2> $(VOID) && rm have_pthread$(EXT) && echo 1 || echo 0)
+HAVE_PTHREAD := $(shell printf '$(NUM_SYMBOL)include <pthread.h>\nint main(void) { return 0; }' | $(CC) $(FLAGS) -o have_pthread$(EXT) -x c - -pthread 2> $(VOID) && rm have_pthread$(EXT) && echo 1 || echo 0)
 HAVE_THREAD := $(shell [ "$(HAVE_PTHREAD)" -eq "1" -o -n "$(filter Windows%,$(OS))" ] && echo 1 || echo 0)
 ifeq ($(HAVE_THREAD), 1)
 THREAD_MSG := ==> building with threading support
@@ -98,7 +103,7 @@ endif
 # zlib detection
 NO_ZLIB_MSG := ==> no zlib, building zstd without .gz support
-HAVE_ZLIB := $(shell printf '\#include <zlib.h>\nint main(void) { return 0; }' | $(CC) $(FLAGS) -o have_zlib$(EXT) -x c - -lz 2> $(VOID) && rm have_zlib$(EXT) && echo 1 || echo 0)
+HAVE_ZLIB := $(shell printf '$(NUM_SYMBOL)include <zlib.h>\nint main(void) { return 0; }' | $(CC) $(FLAGS) -o have_zlib$(EXT) -x c - -lz 2> $(VOID) && rm have_zlib$(EXT) && echo 1 || echo 0)
 ifeq ($(HAVE_ZLIB), 1)
 ZLIB_MSG := ==> building zstd with .gz compression support
 ZLIBCPP = -DZSTD_GZCOMPRESS -DZSTD_GZDECOMPRESS
@@ -109,7 +114,7 @@ endif
 # lzma detection
 NO_LZMA_MSG := ==> no liblzma, building zstd without .xz/.lzma support
-HAVE_LZMA := $(shell printf '\#include <lzma.h>\nint main(void) { return 0; }' | $(CC) $(FLAGS) -o have_lzma$(EXT) -x c - -llzma 2> $(VOID) && rm have_lzma$(EXT) && echo 1 || echo 0)
+HAVE_LZMA := $(shell printf '$(NUM_SYMBOL)include <lzma.h>\nint main(void) { return 0; }' | $(CC) $(FLAGS) -o have_lzma$(EXT) -x c - -llzma 2> $(VOID) && rm have_lzma$(EXT) && echo 1 || echo 0)
 ifeq ($(HAVE_LZMA), 1)
 LZMA_MSG := ==> building zstd with .xz/.lzma compression support
 LZMACPP = -DZSTD_LZMACOMPRESS -DZSTD_LZMADECOMPRESS
@@ -120,7 +125,7 @@ endif
 # lz4 detection
 NO_LZ4_MSG := ==> no liblz4, building zstd without .lz4 support
-HAVE_LZ4 := $(shell printf '\#include <lz4frame.h>\n\#include <lz4.h>\nint main(void) { return 0; }' | $(CC) $(FLAGS) -o have_lz4$(EXT) -x c - -llz4 2> $(VOID) && rm have_lz4$(EXT) && echo 1 || echo 0)
+HAVE_LZ4 := $(shell printf '$(NUM_SYMBOL)include <lz4frame.h>\n$(NUM_SYMBOL)include <lz4.h>\nint main(void) { return 0; }' | $(CC) $(FLAGS) -o have_lz4$(EXT) -x c - -llz4 2> $(VOID) && rm have_lz4$(EXT) && echo 1 || echo 0)
 ifeq ($(HAVE_LZ4), 1)
 LZ4_MSG := ==> building zstd with .lz4 compression support
 LZ4CPP = -DZSTD_LZ4COMPRESS -DZSTD_LZ4DECOMPRESS
EOF
patch -p0 < portable-header-prefix.patch
make
cd ~
cat > compress-zstd <<EOT
#!/bin/sh
./zstd-1.3.4/zstd -19 -T\$NUM_CPU_CORES ubuntu-16.04.3-server-i386.img > /dev/null 2>&1
EOT
chmod +x compress-zstd
