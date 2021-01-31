#!/bin/sh

tar -xvzf qvdpautest-0.5.1.tar.gz
cd qvdpautest-0.5.1/

echo "--- mainwidget.cpp.orig	2012-04-29 09:01:20.009007783 -0400
+++ mainwidget.cpp	2012-04-29 09:01:32.773008163 -0400
@@ -177,7 +177,7 @@
 			QString res = vw->benchMT();
 			printf( "%s", res.toAscii().data() );
 			te->append( vw->getSummary() );
-			setCurrentIndex( 0 );
+			QApplication::exit(0);
 		}
 	}
 	" | patch -p0

echo "--- vdpauwidget.cpp.orig	2010-04-30 05:37:06.000000000 -0500
+++ vdpauwidget.cpp	2013-11-25 09:01:48.070707237 -0600
@@ -2,6 +2,7 @@
 #include <stdio.h>
 #include <stdlib.h>
 #include <time.h>
+#include <unistd.h>
 
 #include <QtGui>
 #include <QX11Info>
" | patch -p0

qmake
make
echo $? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd qvdpautest-0.5.1/
./qvdpautest > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > qvdpautest
chmod +x qvdpautest
