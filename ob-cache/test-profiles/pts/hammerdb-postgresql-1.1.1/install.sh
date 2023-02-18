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

tar -xf HammerDB-4.5-Linux.tar.gz
echo "#!/bin/sh
PGUSER=`basename \$DEBUG_REAL_HOME`
rm -rf \$HOME/pg_/data/*
# initialize database with encoding and locale
\$HOME/pg_/bin/initdb -D \$HOME/pg_/data/db -U \$PGUSER --encoding=SQL_ASCII --locale=C --wal-segsize=1024


export LD_LIBRARY_PATH=\$HOME/pg_/lib/:\$LD_LIBRARY_PATH
PGDATA=\$HOME/pg_/data/db/
PGPORT=7777
export PGDATA
export PGPORT
# start server
SHARED_BUFFER_SIZE=\`echo \"\$SYS_MEMORY * 0.25 / 1\" | bc\`
EFFECTIVE_CACHE_SIZE=\`echo \"\$SYS_MEMORY * 0.5 / 1\" | bc\`
echo \"Shared buffer size is \${SHARED_BUFFER_SIZE}MB\" > \$LOG_FILE
echo \"Effective cache size is \${EFFECTIVE_CACHE_SIZE}MB\" >> \$LOG_FILE
pg_/bin/pg_ctl start -o \"-c autovacuum=true -c max_connections=1000 -c synchronous_commit=off -c shared_buffers=\${SHARED_BUFFER_SIZE}MB -c effective_cache_size=\${EFFECTIVE_CACHE_SIZE}MB -c max_wal_size=16384 -c min_wal_size=8192 -c wal_buffers=512MB -c max_files_per_process=4000\" >> \$LOG_FILE
# wait for server to start
sleep 10
pg_/bin/createdb postgres
pg_/bin/psql --command '\du'
pg_/bin/psql --command '\password postgres'

cd ~/HammerDB-4.5
echo \"puts \\\"SETTING CONFIGURATION\\\"
dbset db pg
diset connection pg_host localhost
diset connection pg_port \$PGPORT
diset tpcc pg_count_ware \$2
diset tpcc pg_partition false
diset tpcc pg_num_vu \$1
diset tpcc pg_superuser \$PGUSER
diset tpcc pg_superuserpass postgres
diset tpcc pg_defaultdbase postgres
diset tpcc pg_user tpcc
diset tpcc pg_pass tpcc
diset tpcc pg_dbase tpcc
print dict
buildschema
waittocomplete\" > schemabuild.tcl

./hammerdbcli auto schemabuild.tcl
echo \"#vi psqlrun.tcl
puts \\\"SETTING CONFIGURATION\\\"
dbset db pg
diset connection pg_host localhost
diset connection pg_port \$PGPORT
diset tpcc pg_superuser \$PGUSER
diset tpcc pg_driver timed
diset tpcc pg_rampup 2
diset tpcc pg_duration 6
diset tpcc pg_vacuum true
vuset logtotemp 1
loadscript
puts \\\"TEST STARTED\\\"
vuset vu \$1
vucreate
vurun
runtimer 500
vudestroy
puts \\\"TEST COMPLETE\\\"\" > psqlrun.tcl

./hammerdbcli auto psqlrun.tcl > \$LOG_FILE 2>&1

cd ~
# stop server
pg_/bin/pg_ctl stop
sleep 3
rm -rf \$HOME/pg_/data/*" > hammerdb-postgresql
chmod +x hammerdb-postgresql
