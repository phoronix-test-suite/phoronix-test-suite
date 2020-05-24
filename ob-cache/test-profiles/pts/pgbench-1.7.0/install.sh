#!/bin/sh

version=10.0
tar -xjf postgresql-${version}.tar.bz2 

rm -rf $HOME/pg_
mkdir -p $HOME/pg_/data/postgresql/extension/
touch $HOME/pg_/data/postgresql/extension/plpgsql.control

# Junk up the root checking code so test profiles can easily run as root
echo "diff -Naur postgresql-10.0.orig/contrib/pg_upgrade/option.c postgresql-10.0/contrib/pg_upgrade/option.c
--- postgresql-10.0.orig/contrib/pg_upgrade/option.c	2015-06-01 15:05:57.000000000 -0400
+++ postgresql-10.0/contrib/pg_upgrade/option.c	2015-06-09 20:15:18.401447066 -0400
@@ -95,8 +95,8 @@
 	}
 
 	/* Allow help and version to be run as root, so do the test here. */
-	if (os_user_effective_id == 0)
-		pg_fatal(\"%s: cannot be run as root\\\n\", os_info.progname);
+	//if (os_user_effective_id == 0)
+	//	pg_fatal(\"%s: cannot be run as root\\\n\", os_info.progname);
 
 	if ((log_opts.internal = fopen_priv(INTERNAL_LOG_FILE, \"a\")) == NULL)
 		pg_fatal(\"cannot write to log file %s\\\n\", INTERNAL_LOG_FILE);
diff -Naur postgresql-10.0.orig/src/bin/initdb/initdb.c postgresql-10.0/src/bin/initdb/initdb.c
--- postgresql-10.0.orig/src/bin/initdb/initdb.c	2015-06-01 15:05:57.000000000 -0400
+++ postgresql-10.0/src/bin/initdb/initdb.c	2015-06-09 20:21:57.424364326 -0400
@@ -794,7 +794,7 @@
 {
 	const char *username;
 
-#ifndef WIN32
+#ifdef defined(WIN32) && !defined(WIN32)
 	if (geteuid() == 0)			/* 0 is root's uid */
 	{
 		fprintf(stderr,
diff -Naur postgresql-10.0.orig/src/bin/pg_ctl/pg_ctl.c postgresql-10.0/src/bin/pg_ctl/pg_ctl.c
--- postgresql-10.0.orig/src/bin/pg_ctl/pg_ctl.c	2015-06-01 15:05:57.000000000 -0400
+++ postgresql-10.0/src/bin/pg_ctl/pg_ctl.c	2015-06-09 20:22:25.360273073 -0400
@@ -2129,7 +2129,7 @@
 	/*
 	 * Disallow running as root, to forestall any possible security holes.
 	 */
-#ifndef WIN32
+#ifdef defined(WIN32) && !defined(WIN32)
 	if (geteuid() == 0)
 	{
 		write_stderr(_(\"%s: cannot be run as root\\\n\"
diff -Naur postgresql-10.0.orig/src/backend/main/main.c postgresql-10.0/src/backend/main/main.c
--- postgresql-10.0.orig/src/backend/main/main.c	2015-06-01 15:05:57.000000000 -0400
+++ postgresql-10.0/src/backend/main/main.c	2015-06-09 20:29:40.324604340 -0400
@@ -391,7 +391,7 @@
 static void
 check_root(const char *progname)
 {
-#ifndef WIN32
+#ifdef defined(WIN32) && !defined(WIN32)
 	if (geteuid() == 0)
 	{
 		write_stderr(\"\\\"root\\\" execution of the PostgreSQL server is not permitted.\\\n\"
@@ -415,7 +415,6 @@
 					 progname);
 		exit(1);
 	}
-#else							/* WIN32 */
 	if (pgwin32_is_admin())
 	{
 		write_stderr(\"Execution of PostgreSQL by a user with administrative permissions is not\\\n\"

" | patch -p0


cd postgresql-${version}
./configure --prefix=$HOME/pg_ --without-readline --without-zlib
make -j $NUM_CPU_JOBS
make -C contrib/pgbench all
# echo $? > ~/install-exit-status
make install
make -C contrib/pgbench install
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
pg_/bin/pg_ctl start -o '-c autovacuum=false'
# wait for server to start
sleep 10

# create test db
pg_/bin/createdb pgbench

# set up tables
case \$1 in
	\"BUFFER_TEST\")
		SCALING_FACTOR=\`echo \"\$SYS_MEMORY * 0.003\" | bc\`
	;;
	\"MOSTLY_CACHE\")
		SCALING_FACTOR=\`echo \"\$SYS_MEMORY * 0.2\" | bc\`
	;;
	\"ON_DISK\")
		SCALING_FACTOR=\`echo \"\$SYS_MEMORY * 0.8\" | bc\`
	;;
esac

pg_/bin/pgbench -i -s \$SCALING_FACTOR pgbench

case \$2 in
	\"SINGLE_THREAD\")
		PGBENCH_ARGS=\"-c 1\"
	;;
	\"NORMAL_LOAD\")
		PGBENCH_ARGS=\"-j \$NUM_CPU_CORES -c \$((\$NUM_CPU_CORES*4))\"
	;;
	\"HEAVY_CONTENTION\")
		PGBENCH_ARGS=\"-j \$((\$NUM_CPU_CORES*2)) -c \$((\$NUM_CPU_CORES*16))\"
	;;
esac

case \$3 in
	\"READ_WRITE\")
		PGBENCH_MORE_ARGS=\"\"
	;;
	\"READ_ONLY\")
		PGBENCH_MORE_ARGS=\"-S\"
	;;
esac

# run the test 
pg_/bin/pgbench \$PGBENCH_ARGS \$PGBENCH_MORE_ARGS -T 60 pgbench >\$LOG_FILE 2>&1
# drop test db
pg_/bin/dropdb pgbench
# stop server
pg_/bin/pg_ctl stop" > pgbench
chmod +x pgbench

