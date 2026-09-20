#!/bin/sh
version=18.0
tar -xjf postgresql-${version}.tar.bz2 
rm -rf $HOME/pg_
mkdir -p $HOME/pg_/data/postgresql/extension/
touch $HOME/pg_/data/postgresql/extension/plpgsql.control
# Junk up the root checking code so test profiles can easily run as root
patch -p0 <<'EOF'
diff -Naur postgresql-18.0.orig/src/backend/main/main.c postgresql-18.0/src/backend/main/main.c
--- postgresql-18.0.orig/src/backend/main/main.c	2025-09-22 15:11:33.000000000 -0500
+++ postgresql-18.0/src/backend/main/main.c	2025-10-02 15:22:21.487434732 -0500
@@ -70,7 +70,7 @@
 int
 main(int argc, char *argv[])
 {
-	bool		do_check_root = true;
+	bool		do_check_root = false;
 	DispatchOption dispatch_option = DISPATCH_POSTMASTER;
 
 	reached_main = true;
diff -Naur postgresql-18.0.orig/src/bin/initdb/initdb.c postgresql-18.0/src/bin/initdb/initdb.c
--- postgresql-18.0.orig/src/bin/initdb/initdb.c	2025-09-22 15:11:33.000000000 -0500
+++ postgresql-18.0/src/bin/initdb/initdb.c	2025-10-02 15:22:52.034821709 -0500
@@ -816,7 +816,7 @@
 {
 	const char *username;
 
-#ifndef WIN32
+#if 0
 	if (geteuid() == 0)			/* 0 is root's uid */
 	{
 		pg_log_error("cannot be run as root");
diff -Naur postgresql-18.0.orig/src/bin/pg_ctl/pg_ctl.c postgresql-18.0/src/bin/pg_ctl/pg_ctl.c
--- postgresql-18.0.orig/src/bin/pg_ctl/pg_ctl.c	2025-09-22 15:11:33.000000000 -0500
+++ postgresql-18.0/src/bin/pg_ctl/pg_ctl.c	2025-10-02 15:23:46.144033746 -0500
@@ -2253,7 +2253,7 @@
 	/*
 	 * Disallow running as root, to forestall any possible security holes.
 	 */
-#ifndef WIN32
+#if 0
 	if (geteuid() == 0)
 	{
 		write_stderr(_("%s: cannot be run as root\n"
diff -Naur postgresql-18.0.orig/src/bin/pg_upgrade/option.c postgresql-18.0/src/bin/pg_upgrade/option.c
--- postgresql-18.0.orig/src/bin/pg_upgrade/option.c	2025-09-22 15:11:33.000000000 -0500
+++ postgresql-18.0/src/bin/pg_upgrade/option.c	2025-10-02 15:24:18.379686139 -0500
@@ -105,10 +105,6 @@
 		}
 	}
 
-	/* Allow help and version to be run as root, so do the test here. */
-	if (os_user_effective_id == 0)
-		pg_fatal("%s: cannot be run as root", os_info.progname);
-
 	while ((option = getopt_long(argc, argv, "b:B:cd:D:j:kNo:O:p:P:rs:U:v",
 								 long_options, &optindex)) != -1)
 	{
EOF

cd postgresql-${version}
./configure --prefix=$HOME/pg_ --without-readline --without-zlib

if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
	gmake -C contrib/pgbench all
	# echo $? > ~/install-exit-status
	gmake install
	gmake -C contrib/pgbench install
else
	make -j $NUM_CPU_CORES
	make -C contrib/pgbench all
	# echo $? > ~/install-exit-status
	make install
	make -C contrib/pgbench install
fi
cd ~
rm -rf postgresql-${version}/
rm -rf pg_/doc/
# initialize database with encoding and locale
$HOME/pg_/bin/initdb -D $HOME/pg_/data/db --encoding=SQL_ASCII --locale=C
echo "#!/bin/sh
PGDATA=\$HOME/pg_/data/db/
PGPORT=7777
export PGDATA
export PGPORT
# start server
# Since postgresql-18 does have very limited NUMA support and server uses 8 threads only in this run
# There is no sense to run the SQL server on several NUMA nodes 
# This _if_ handles both \"numactl not found\" and \"No NUMA available on this system\" errors
if [ ! `numactl -H >/dev/null 2>&1` ] && [ `numactl -H | grep 'available:' | awk '{print \$2}'` -gt 1 ]
then
	LAUNCHING_NODE=0 # Universal, might be changed to the node where a disk controller is attached
	# To avoid count nodes either without memory or with HBM memory counting memory on the launching node only
	NODE_MEMORY=\`numactl -H | grep \"node \$LAUNCHING_NODE size\" | awk '{print \$4}'\`
	# Recommended shared_buffers size is 25% of memory and bigger WAL buffer size, using 25% for each
	# https://www.postgresql.org/docs/18/runtime-config-resource.html
	SHARED_BUFFER_SIZE=\`echo \"\$NODE_MEMORY * 0.25 / 1\" | bc\`
	WAL_BUFFER_SIZE=\${SHARED_BUFFER_SIZE}
	echo \"Buffer size is \${SHARED_BUFFER_SIZE}MB\" > \$LOG_FILE
	echo \"WAL Checkpoint buffer size is \${WAL_BUFFER_SIZE}MB\" >> \$LOG_FILE
	# echo Starting SQL server on NUMA node \$LAUNCHING_NODE
	numactl -N \$LAUNCHING_NODE pg_/bin/pg_ctl start -o \"-c max_connections=6000 -c shared_buffers=\${SHARED_BUFFER_SIZE}MB -c max_wal_size=\${WAL_BUFFER_SIZE}MB\"
else
	# echo No NUMA/numactl support found, starting server in older UMA way
	SHARED_BUFFER_SIZE=\`echo \"\$SYS_MEMORY * 0.25 / 1\" | bc\`
	SHARED_BUFFER_SIZE=\$(( \$SHARED_BUFFER_SIZE < 8192 ? \$SHARED_BUFFER_SIZE : 8192 ))
	echo \"Buffer size is \${SHARED_BUFFER_SIZE}MB\" > \$LOG_FILE
	pg_/bin/pg_ctl start -o \"-c max_connections=6000 -c shared_buffers=\${SHARED_BUFFER_SIZE}MB\"
fi
# wait for server to start
sleep 10

# create test db
pg_/bin/createdb pgbench

# set up tables
pg_/bin/pgbench -i \$1 \$2 -n pgbench

# run the test 
pg_/bin/pgbench --protocol=prepared -j \$NUM_CPU_CORES \$@ -n -T 120 -r pgbench >>\$LOG_FILE 2>&1
# drop test db
pg_/bin/dropdb pgbench
# stop server
pg_/bin/pg_ctl stop" > pgbench
chmod +x pgbench
