#!/bin/sh
version=15.0
tar -xjf postgresql-${version}.tar.bz2 
rm -rf $HOME/pg_
mkdir -p $HOME/pg_/data/postgresql/extension/
touch $HOME/pg_/data/postgresql/extension/plpgsql.control
# Junk up the root checking code so test profiles can easily run as root
patch -p0 <<'EOF'
diff -Naur postgresql-15.0.orig/src/backend/main/main.c postgresql-15.0/src/backend/main/main.c
--- postgresql-15.0.orig/src/backend/main/main.c	2022-10-10 16:57:37.000000000 -0400
+++ postgresql-15.0/src/backend/main/main.c	2022-10-13 10:39:56.357527710 -0400
@@ -62,7 +62,7 @@
 int
 main(int argc, char *argv[])
 {
-	bool		do_check_root = true;
+	bool		do_check_root = false;
 
 	/*
 	 * If supported on the current platform, set up a handler to be called if
diff -Naur postgresql-15.0.orig/src/bin/initdb/initdb.c postgresql-15.0/src/bin/initdb/initdb.c
--- postgresql-15.0.orig/src/bin/initdb/initdb.c	2022-10-10 16:57:37.000000000 -0400
+++ postgresql-15.0/src/bin/initdb/initdb.c	2022-10-13 10:40:42.628867350 -0400
@@ -592,7 +592,7 @@
 {
 	const char *username;
 
-#ifndef WIN32
+#if 0
 	if (geteuid() == 0)			/* 0 is root's uid */
 	{
 		pg_log_error("cannot be run as root");
diff -Naur postgresql-15.0.orig/src/bin/pg_ctl/pg_ctl.c postgresql-15.0/src/bin/pg_ctl/pg_ctl.c
--- postgresql-15.0.orig/src/bin/pg_ctl/pg_ctl.c	2022-10-10 16:57:37.000000000 -0400
+++ postgresql-15.0/src/bin/pg_ctl/pg_ctl.c	2022-10-13 10:41:06.324519458 -0400
@@ -2339,7 +2339,7 @@
 	/*
 	 * Disallow running as root, to forestall any possible security holes.
 	 */
-#ifndef WIN32
+#if 0
 	if (geteuid() == 0)
 	{
 		write_stderr(_("%s: cannot be run as root\n"
diff -Naur postgresql-15.0.orig/src/bin/pg_upgrade/option.c postgresql-15.0/src/bin/pg_upgrade/option.c
--- postgresql-15.0.orig/src/bin/pg_upgrade/option.c	2022-10-10 16:57:37.000000000 -0400
+++ postgresql-15.0/src/bin/pg_upgrade/option.c	2022-10-13 10:41:35.916076889 -0400
@@ -95,10 +95,6 @@
 		}
 	}
 
-	/* Allow help and version to be run as root, so do the test here. */
-	if (os_user_effective_id == 0)
-		pg_fatal("%s: cannot be run as root\n", os_info.progname);
-
 	while ((option = getopt_long(argc, argv, "d:D:b:B:cj:kNo:O:p:P:rs:U:v",
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
SHARED_BUFFER_SIZE=\`echo \"\$SYS_MEMORY * 0.25 / 1\" | bc\`
SHARED_BUFFER_SIZE=\$(( \$SHARED_BUFFER_SIZE < 8192 ? \$SHARED_BUFFER_SIZE : 8192 ))
echo \"Buffer size is \${SHARED_BUFFER_SIZE}MB\" > \$LOG_FILE
pg_/bin/pg_ctl start -o \"-c max_connections=6000 -c shared_buffers=\${SHARED_BUFFER_SIZE}MB\"
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
