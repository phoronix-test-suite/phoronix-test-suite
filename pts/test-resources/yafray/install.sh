#!/bin/sh

tar -xvf yafray-0.0.9.tar.gz
tar -jxvf yafray-render-1.tar.bz2

mkdir $HOME/yafray_

echo "--- linux-settings.py.orig	2008-06-02 14:00:50.000000000 -0400
+++ linux-settings.py	2008-06-02 14:01:11.000000000 -0400
@@ -8,7 +8,7 @@
 
 def init(args): 
 	global prefix
-	prefix = args.get('prefix','/usr/local')
+	prefix = \"$HOME/yafray_\"
 
 def get_libpath(args): return prefix+\"/lib\"
 def get_pluginpath(args): return prefix+\"/lib/yafray\"

--- SConstruct.orig	2008-06-02 15:00:15.000000000 -0400
+++ SConstruct	2008-06-02 15:00:35.000000000 -0400
@@ -3,7 +3,7 @@
 import configio
 import globalinfo
 
-prefix=ARGUMENTS.get('prefix','/usr/local')
+prefix=\"$HOME/yafray_/\"
 
 ficheros = {
 'darwin' : 'darwin-settings',
" > yafray/install-patch

cd yafray/
patch -p0 < install-patch
scons
scons install
cd ..
rm -rf yafray/

cp yafray_/etc/gram.yafray .

echo "#!/bin/sh
export LD_LIBRARY_PATH=\"$HOME/yafray_/lib/:\$LD_LIBRARY_PATH\"
\$TIMER_START
./yafray_/bin/yafray -c \$NUM_CPU_CORES YBtest.xml 2>&1
\$TIMER_STOP" > yafray-run
chmod +x yafray-run
