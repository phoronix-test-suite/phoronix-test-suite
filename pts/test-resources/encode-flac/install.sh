#!/bin/sh

mkdir $HOME/flac_

tar -zxvf flac-1.2.1.tar.gz

echo "diff -Naur flac-1.2.1-orig/examples/cpp/encode/file/main.cpp flac-1.2.1/examples/cpp/encode/file/main.cpp
--- flac-1.2.1-orig/examples/cpp/encode/file/main.cpp	2007-09-13 09:58:03.000000000 -0600
+++ flac-1.2.1/examples/cpp/encode/file/main.cpp	2007-11-18 12:59:45.000000000 -0600
@@ -30,6 +30,7 @@
 
 #include <stdio.h>
 #include <stdlib.h>
+#include <cstring>
 #include \"FLAC++/metadata.h\"
 #include \"FLAC++/encoder.h\"
 " | patch -p0

cd flac-1.2.1/
./configure --prefix=$HOME/flac_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf flac-1.2.1/
rm -rf flac_/share/

echo "#!/bin/sh
./flac_/bin/flac -s --best --totally-silent \$TEST_EXTENDS/pts-trondheim.wav -f -o /dev/null 2>&1
echo \$? > ~/test-exit-status" > flac
chmod +x flac
