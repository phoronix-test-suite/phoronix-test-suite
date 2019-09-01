#!/bin/sh

tar -xzvf memcached-1.5.10.tar.gz
cd memcached-1.5.10
./configure
make
echo $? > ~/install-exit-status

cd ~
tar -xzvf mcperf-0.1.1.tar.gz
cd mcperf-0.1.1

# Allow running as root for benchmark
patch -p0 <<'EOF'
--- memcached.c.orig	2018-10-03 19:10:08.431811423 -0400
+++ memcached.c	2018-10-03 19:10:22.899799908 -0400
@@ -7589,8 +7589,8 @@
     /* lose root privileges if we have them */
     if (getuid() == 0 || geteuid() == 0) {
         if (username == 0 || *username == '\0') {
-            fprintf(stderr, "can't run as root without the -u switch\n");
-            exit(EX_USAGE);
+        //    fprintf(stderr, "can't run as root without the -u switch\n");
+        //    exit(EX_USAGE);
         }
         if ((pw = getpwnam(username)) == 0) {
             fprintf(stderr, "can't find the user %s to switch to\n", username);

EOF


./configure
make

cd ~

echo "#!/bin/sh
cd ~/memcached-1.5.10
./memcached -d
MEMCACHED_PID=\$!
sleep 3

cd ~/mcperf-0.1.1
./src/mcperf \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

kill \$MEMCACHED_PID
sleep 3" > mcperf

chmod +x mcperf
