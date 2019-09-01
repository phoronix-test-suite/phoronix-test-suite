#!/bin/sh

tar -jxvf netperf-2.7.0.tar.bz2
cd netperf-2.7.0

if [ "X$CFLAGS_OVERRIDE" = "X" ]
then
          CFLAGS="$CFLAGS -O3 -march=native"
else
          CFLAGS="$CFLAGS_OVERRIDE"
fi

if [ "$OS_TYPE" = "BSD" ]
then
# https://github.com/HewlettPackard/netperf/commit/328fe3b56a8753f6f700aac2b2df84dda5ce93a3.patch
	cat > 328fe3b56a8753f6f700aac2b2df84dda5ce93a3.patch <<EOT
From 328fe3b56a8753f6f700aac2b2df84dda5ce93a3 Mon Sep 17 00:00:00 2001
From: raj <raj@5bbd99f3-5903-0410-b283-f1d88047b228>
Date: Fri, 7 Aug 2015 15:49:52 +0000
Subject: [PATCH] Some additional FreeBSD fixes from Andrew Gallatin.

---
 AUTHORS      | 3 ++-
 configure    | 4 ++--
 configure.ac | 4 ++--
 src/dscp.c   | 3 +++
 4 files changed, 9 insertions(+), 5 deletions(-)

diff --git a/AUTHORS b/AUTHORS
index b1a17fd..4dae415 100644
--- a/AUTHORS
+++ b/AUTHORS
@@ -107,6 +107,7 @@ calibration-free
 fixes to configure to recognize Solaris 11
 fixes to netcpu_procstat.c for later linux kernels
 workarounds to get Linux to report ENOBUFS on TX queue overflows
+FreeBSD fixes
 
 Mark Cooper
 pointing-out the need for -lresolv when compiling -DDO_DNS on RedHat
@@ -306,4 +307,4 @@ the UDP socket connected at the netserver side.
 
 Weijia Song - a fix inspiration for a NULL pointer problem.
 
-Gisle Vanem - some Windows compilation fixes
\ No newline at end of file
+Gisle Vanem - some Windows compilation fixes
diff --git a/configure b/configure
index 3935873..8fd2227 100755
--- a/configure
+++ b/configure
@@ -6567,7 +6567,7 @@ fi
 done
 
 		case "$host" in
-		*-*-freebsd78.*)
+		*-*-freebsd[7-9].* | *-*-freebsd1[0-1].* )
 			# FreeBSD 7.x and later SCTP support doesn't need -lsctp.
 			;;
 		*)
@@ -7142,7 +7142,7 @@ ac_cv_lib_kstat=ac_cv_lib_kstat_main
 			enable_cpuutil="kstat - auto"
 			NETCPU_SOURCE="kstat"
 			;;
-                     *-*-freebsd[4-8].* | *-*-netbsd[1-9].* )
+                     *-*-freebsd[4-9].* | *-*-freebsd1[0-1].* | *-*-netbsd[1-9].* )
 			use_cpuutil=true
 
 $as_echo "#define USE_SYSCTL /**/" >>confdefs.h
diff --git a/configure.ac b/configure.ac
index 4c01090..caba026 100644
--- a/configure.ac
+++ b/configure.ac
@@ -467,7 +467,7 @@ case "$enable_sctp" in
 #include <sys/socket.h>
 ]])
 		case "$host" in
-		*-*-freebsd[78].*)
+		*-*-freebsd[[7-9]].* | *-*-freebsd1[[0-1]].* )
 			# FreeBSD 7.x and later SCTP support doesn't need -lsctp.
 			;;
 		*)
@@ -699,7 +699,7 @@ case "$enable_cpuutil" in
 			enable_cpuutil="kstat - auto"
 			NETCPU_SOURCE="kstat"
 			;;
-                     *-*-freebsd[[4-8]].* | *-*-netbsd[[1-9]].* )
+                     *-*-freebsd[[4-9]].* | *-*-freebsd1[[0-1]].* | *-*-netbsd[[1-9]].* )
 			use_cpuutil=true
 			AC_DEFINE([USE_SYSCTL],,[Use MumbleBSD's sysctl interface to measure CPU util.])
 			enable_cpuutil="sysctl - auto"
diff --git a/src/dscp.c b/src/dscp.c
index 30fcdac..1b77624 100644
--- a/src/dscp.c
+++ b/src/dscp.c
@@ -54,6 +54,9 @@ const char * iptos2str(int iptos);
  */
 
 #if HAVE_NETINET_IN_SYSTM_H
+#if defined(__FreeBSD__)
+#include <sys/types.h>
+#endif
 #include <netinet/in_systm.h>
 #endif
 #if HAVE_NETINET_IP_H
EOT
	patch -p1 < 328fe3b56a8753f6f700aac2b2df84dda5ce93a3.patch
fi

./configure CFLAGS="$CFLAGS"
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd netperf-2.7.0
./src/netperf -P 0 -v 0 \$@ | grep -v is  > \$LOG_FILE
echo \$? > ~/test-exit-status" > netperf
chmod +x netperf
