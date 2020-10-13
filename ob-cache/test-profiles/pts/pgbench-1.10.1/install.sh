#!/bin/sh

version=13.0
tar -xjf postgresql-${version}.tar.bz2 

rm -rf $HOME/pg_
mkdir -p $HOME/pg_/data/postgresql/extension/
touch $HOME/pg_/data/postgresql/extension/plpgsql.control

# Junk up the root checking code so test profiles can easily run as root
patch -p0 <<'EOF'
diff -Naur postgresql-13.0.orig/src/backend/main/main.c postgresql-13.0/src/backend/main/main.c
--- postgresql-13.0.orig/src/backend/main/main.c	2020-09-21 16:47:36.000000000 -0400
+++ postgresql-13.0/src/backend/main/main.c	2020-09-24 11:04:01.332286329 -0400
@@ -59,7 +59,7 @@
 int
 main(int argc, char *argv[])
 {
-	bool		do_check_root = true;
+	bool		do_check_root = false;
 
 	/*
 	 * If supported on the current platform, set up a handler to be called if
diff -Naur postgresql-13.0.orig/src/bin/initdb/initdb.c postgresql-13.0/src/bin/initdb/initdb.c
--- postgresql-13.0.orig/src/bin/initdb/initdb.c	2020-09-21 16:47:36.000000000 -0400
+++ postgresql-13.0/src/bin/initdb/initdb.c	2020-09-24 11:05:19.272500559 -0400
@@ -631,7 +631,7 @@
 {
 	const char *username;
 
-#ifndef WIN32
+#if 0
 	if (geteuid() == 0)			/* 0 is root's uid */
 	{
 		pg_log_error("cannot be run as root");
diff -Naur postgresql-13.0.orig/src/bin/pg_ctl/pg_ctl.c postgresql-13.0/src/bin/pg_ctl/pg_ctl.c
--- postgresql-13.0.orig/src/bin/pg_ctl/pg_ctl.c	2020-09-21 16:47:36.000000000 -0400
+++ postgresql-13.0/src/bin/pg_ctl/pg_ctl.c	2020-09-24 11:05:54.732601534 -0400
@@ -2310,7 +2310,7 @@
 	/*
 	 * Disallow running as root, to forestall any possible security holes.
 	 */
-#ifndef WIN32
+#if 0
 	if (geteuid() == 0)
 	{
 		write_stderr(_("%s: cannot be run as root\n"
diff -Naur postgresql-13.0.orig/src/bin/pg_upgrade/option.c postgresql-13.0/src/bin/pg_upgrade/option.c
--- postgresql-13.0.orig/src/bin/pg_upgrade/option.c	2020-09-21 16:47:36.000000000 -0400
+++ postgresql-13.0/src/bin/pg_upgrade/option.c	2020-09-24 11:06:50.900765457 -0400
@@ -97,9 +97,6 @@
 		}
 	}
 
-	/* Allow help and version to be run as root, so do the test here. */
-	if (os_user_effective_id == 0)
-		pg_fatal("%s: cannot be run as root\n", os_info.progname);
 
 	while ((option = getopt_long(argc, argv, "d:D:b:B:cj:ko:O:p:P:rs:U:v",
 								 long_options, &optindex)) != -1)
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
pg_/bin/pg_ctl start -o '-c autovacuum=false -c max_connections=300'
# wait for server to start
sleep 10

# create test db
pg_/bin/createdb pgbench

# set up tables

pg_/bin/pgbench -i \$1 \$2 -n pgbench

# run the test 
pg_/bin/pgbench -j \$NUM_CPU_CORES \$@ -n -T 15 -r pgbench >\$LOG_FILE 2>&1
# drop test db
pg_/bin/dropdb pgbench
# stop server
pg_/bin/pg_ctl stop" > pgbench
chmod +x pgbench
