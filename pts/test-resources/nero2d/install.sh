#!/bin/sh

mkdir $HOME/nero2d_

tar -zxvf nero2d-2.0.2.tar.gz

patch -p0 <<'EOT'
--- nero2d-2.0.2.orig/src/nexus/nexus.cpp	2009-04-03 09:42:29.000000000 -0400
+++ nero2d-2.0.2/src/nexus/nexus.cpp	2009-06-13 21:52:42.589304348 -0400
@@ -21,6 +21,8 @@
 #include "nexus.h"
 #include "engine.h"
 #include "base.h"
+#include <cstring>
+#include <cstdio>
 
 using namespace std;
 
EOT

cd nero2d-2.0.2/
./configure --prefix=$HOME/nero2d_
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
make install
cd ..
rm -rf nero2d-2.0.2/

echo "#!/bin/sh
./nero2d_/bin/nero2d \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > nero2d
chmod +x nero2d
