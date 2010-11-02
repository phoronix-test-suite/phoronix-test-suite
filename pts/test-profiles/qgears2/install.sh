#!/bin/sh

tar -jxf qgears2.tar.bz2
cd qgears2/

chmod +w commonrenderer.cpp
echo "--- commonrenderer.cpp.orig	2008-11-02 16:19:16.000000000 -0500
+++ commonrenderer.cpp	2008-11-02 16:20:33.000000000 -0500
@@ -31,6 +31,7 @@
 double gear1_rotation = 35;
 double gear2_rotation = 24;
 double gear3_rotation = 33.5;
+int frame_report_count = 0;
 
 #define LINEWIDTH 3
 
@@ -83,7 +84,13 @@
 
     ++frame_cnt;
     if (FRAME_COUNT_INTERVAL == frame_cnt)
+    {
         printFrameRate();
+        frame_report_count++;
+    }
+
+    if(frame_report_count == 40)
+        exit(0);
 }
 
 QPainterPath CommonRenderer::gearPath(double inner_radius, double outer_radius," | patch -p0


qmake
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
cd ..

echo "#!/bin/sh
cd qgears2/
./qgears \$1 \$2 > \$LOG_FILE 2>&1" > qgears2-run
chmod +x qgears2-run
