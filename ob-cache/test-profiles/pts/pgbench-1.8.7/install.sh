#!/bin/sh

version=10.3
tar -xjf postgresql-${version}.tar.bz2 

rm -rf $HOME/pg_
mkdir -p $HOME/pg_/data/postgresql/extension/
touch $HOME/pg_/data/postgresql/extension/plpgsql.control

# Junk up the root checking code so test profiles can easily run as root
patch -p0 <<'EOF'
diff -ur postgresql-10.3.orig/src/backend/main/main.c postgresql-10.3/src/backend/main/main.c
--- postgresql-10.3.orig/src/backend/main/main.c	2018-02-26 22:10:47.000000000 +0000
+++ postgresql-10.3/src/backend/main/main.c	2018-03-22 21:57:44.050950561 +0000
@@ -385,7 +385,7 @@
 static void
 check_root(const char *progname)
 {
-#ifndef WIN32
+#ifdef defined(WIN32) && !defined(WIN32)
 	if (geteuid() == 0)
 	{
 		write_stderr("\"root\" execution of the PostgreSQL server is not permitted.\n"
@@ -409,7 +409,6 @@
 					 progname);
 		exit(1);
 	}
-#else							/* WIN32 */
 	if (pgwin32_is_admin())
 	{
 		write_stderr("Execution of PostgreSQL by a user with administrative permissions is not\n"
diff -ur postgresql-10.3.orig/src/bin/initdb/initdb.c postgresql-10.3/src/bin/initdb/initdb.c
--- postgresql-10.3.orig/src/bin/initdb/initdb.c	2018-02-26 22:10:47.000000000 +0000
+++ postgresql-10.3/src/bin/initdb/initdb.c	2018-03-22 21:54:09.900940349 +0000
@@ -606,7 +606,7 @@
 {
 	const char *username;
 
-#ifndef WIN32
+#ifdef defined(WIN32) && !defined(WIN32)
 	if (geteuid() == 0)			/* 0 is root's uid */
 	{
 		fprintf(stderr,
diff -ur postgresql-10.3.orig/src/bin/pg_ctl/pg_ctl.c postgresql-10.3/src/bin/pg_ctl/pg_ctl.c
--- postgresql-10.3.orig/src/bin/pg_ctl/pg_ctl.c	2018-02-26 22:10:47.000000000 +0000
+++ postgresql-10.3/src/bin/pg_ctl/pg_ctl.c	2018-03-22 21:57:02.145948563 +0000
@@ -2138,7 +2138,7 @@
 	/*
 	 * Disallow running as root, to forestall any possible security holes.
 	 */
-#ifndef WIN32
+#ifdef defined(WIN32) && !defined(WIN32)
 	if (geteuid() == 0)
 	{
 		write_stderr(_("%s: cannot be run as root\n"
diff -ur postgresql-10.3.orig/src/bin/pg_upgrade/option.c postgresql-10.3/src/bin/pg_upgrade/option.c
--- postgresql-10.3.orig/src/bin/pg_upgrade/option.c	2018-02-26 22:10:47.000000000 +0000
+++ postgresql-10.3/src/bin/pg_upgrade/option.c	2018-03-22 21:53:00.473937039 +0000
@@ -94,8 +94,8 @@
 	}
 
 	/* Allow help and version to be run as root, so do the test here. */
-	if (os_user_effective_id == 0)
-		pg_fatal("%s: cannot be run as root\n", os_info.progname);
+	/* if (os_user_effective_id == 0) */
+	/* 	pg_fatal("%s: cannot be run as root\n", os_info.progname); */
 
 	if ((log_opts.internal = fopen_priv(INTERNAL_LOG_FILE, "a")) == NULL)
 		pg_fatal("could not write to log file \"%s\"\n", INTERNAL_LOG_FILE);
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
		SCALING_FACTOR=\`echo \"\$SYS_MEMORY * 0.6\" | bc\`
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
