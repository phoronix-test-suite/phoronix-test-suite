#!/bin/sh

tar -xjf bwfirt-0.666.tar.bz2

echo "diff -Naur bwfirt.orig/SConstruct bwfirt/SConstruct
--- bwfirt.orig/SConstruct	2007-12-06 12:45:41.000000000 -0500
+++ bwfirt/SConstruct	2009-03-26 13:50:40.000000000 -0400
@@ -51,7 +51,7 @@
 compilerInfo = ""
 compilerstatus = commands.getstatusoutput(cxx + \" --version\")
 
-if compilerstatus[1].find(\"GCC\") >= 0:
+if compilerstatus[1].find(\"free\") >= 0:
 	GNUCompiler = True
 	compilerInfo = compilerstatus[1]
 else:
diff -Naur bwfirt.orig/src/base/FloatImage.h bwfirt/src/base/FloatImage.h
--- bwfirt.orig/src/base/FloatImage.h	2007-12-06 12:45:42.000000000 -0500
+++ bwfirt/src/base/FloatImage.h	2009-03-26 13:58:40.000000000 -0400
@@ -27,6 +27,8 @@
 #include <png.h>
 #endif
 
+#include <cstring>
+
 /**
  * Floating point image representation & handling
  */" | patch -p0

cd bwfirt/
scons
cd ..

echo "#!/bin/sh
cd bwfirt/
\$TIMER_START
./bin/bwfirt -s scenes/ulmBox.scene -t \$NUM_CPU_CORES -c scenes/ulmBox.cam -b -p 196 --kernel funky-kd -x 800 -y 600 > \$LOG_FILE 2>&1
\$TIMER_STOP" > bwfirt-benchmark
chmod +x bwfirt-benchmark
