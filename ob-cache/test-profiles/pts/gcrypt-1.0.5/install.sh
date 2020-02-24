#!/bin/sh

mkdir $HOME/gpgerror
tar -jxf libgpg-error-1.7.tar.bz2
cd libgpg-error-1.7/
./configure --prefix=$HOME/gpgerror
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
make install
cd ~
rm -rf libgpg-error-1.7/

tar -jxf libgcrypt-1.4.4.tar.bz2
cd libgcrypt-1.4.4/
patch -p0 <<'EOT'
--- tests/Makefile.in.orig 2009-01-22 12:16:51.000000000 -0600
+++ tests/Makefile.in 2014-01-22 19:30:52.289933436 -0600
@@ -332,7 +332,7 @@
 # a built header.
 AM_CPPFLAGS = -I../src -I$(top_srcdir)/src
 AM_CFLAGS = $(GPG_ERROR_CFLAGS)
-LDADD = ../src/libgcrypt.la $(DL_LIBS)
+LDADD = ../src/libgcrypt.la $(DL_LIBS) $(GPG_ERROR_LIBS)
 EXTRA_DIST = README rsa-16k.key cavs_tests.sh cavs_driver.pl
 all: all-am
 
--- tests/Makefile.am.orig	2008-12-02 11:51:35.000000000 +0100
+++ tests/Makefile.am	2014-08-10 13:43:11.137432819 +0200
@@ -36,7 +36,7 @@
 AM_CPPFLAGS = -I../src -I$(top_srcdir)/src
 AM_CFLAGS = $(GPG_ERROR_CFLAGS)
 
-LDADD = ../src/libgcrypt.la $(DL_LIBS)
+LDADD = ../src/libgcrypt.la $(DL_LIBS) $(GPG_ERROR_LIBS)
 
 EXTRA_PROGRAMS = testapi pkbench
 noinst_PROGRAMS = $(TESTS) fipsdrv
EOT

./configure --with-gpg-error-prefix=$HOME/gpgerror
make -j $NUM_CPU_JOBS
echo \$? > ~/install-exit-status
cd ~

echo "#!/bin/sh
./libgcrypt-1.4.4/tests/benchmark \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > gcrypt
chmod +x gcrypt


