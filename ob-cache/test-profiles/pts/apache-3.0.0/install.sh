#!/bin/bash
mkdir $HOME/httpd_
tar -xf http-test-files-1.tar.xz
tar -xf httpd-2.4.56.tar.bz2
tar -xf apr-util-1.6.3.tar.bz2
tar -xf apr-1.7.2.tar.bz2
mv apr-1.7.2 httpd-2.4.56/srclib/apr
mv apr-util-1.6.3 httpd-2.4.56/srclib/apr-util
cd httpd-2.4.56/
./configure --prefix=$HOME/httpd_ --with-included-apr
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
	gmake install
	echo $? > ~/install-exit-status
else
	make -j $NUM_CPU_CORES
	make install
	echo $? > ~/install-exit-status
fi
cd ~
rm -rf httpd-2.4.56/
rm -rf httpd_/manual/
mv -f http-test-files/* httpd_/htdocs/
echo "
--- httpd_/conf/httpd.conf.orig	2009-05-05 11:45:32.000000000 -0400
+++ httpd_/conf/httpd.conf	2009-05-05 11:46:09.000000000 -0400
@@ -37,7 +37,7 @@
 # prevent Apache from glomming onto all bound IP addresses.
 #
 #Listen 12.34.56.78:80
-Listen 80
+Listen 8088
 
 #
 # Dynamic Shared Object (DSO) Support
" > CHANGE-PORT.patch
patch -p0 < CHANGE-PORT.patch
rm -rf wrk-4.2.0
tar -xf wrk-4.2.0.tar.gz
cd wrk-4.2.0
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
./wrk-4.2.0/wrk -t \$NUM_CPU_CORES \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > apache
chmod +x apache
