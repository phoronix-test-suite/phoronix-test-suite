#!/bin/sh

version=12.0
tar -xjf postgresql-${version}.tar.bz2 

rm -rf $HOME/pg_
mkdir -p $HOME/pg_/data/postgresql/extension/
touch $HOME/pg_/data/postgresql/extension/plpgsql.control

# Junk up the root checking code so test profiles can easily run as root
patch -p0 <<'EOF'
diff -Naur postgresql-12.0.orig/src/backend/main/main.c postgresql-12.0/src/backend/main/main.c
--- postgresql-12.0.orig/src/backend/main/main.c	2019-09-30 16:06:55.000000000 -0400
+++ postgresql-12.0/src/backend/main/main.c	2019-10-03 09:21:03.854515512 -0400
@@ -59,7 +59,7 @@
 int
 main(int argc, char *argv[])
 {
-	bool		do_check_root = true;
+	bool		do_check_root = false;
 
 	/*
 	 * If supported on the current platform, set up a handler to be called if
diff -Naur postgresql-12.0.orig/src/bin/initdb/initdb.c postgresql-12.0/src/bin/initdb/initdb.c
--- postgresql-12.0.orig/src/bin/initdb/initdb.c	2019-09-30 16:06:55.000000000 -0400
+++ postgresql-12.0/src/bin/initdb/initdb.c	2019-10-03 09:19:35.633986057 -0400
@@ -645,17 +645,6 @@
 {
 	const char *username;
 
-#ifndef WIN32
-	if (geteuid() == 0)			/* 0 is root's uid */
-	{
-		pg_log_error("cannot be run as root");
-		fprintf(stderr,
-				_("Please log in (using, e.g., \"su\") as the (unprivileged) user that will\n"
-				  "own the server process.\n"));
-		exit(1);
-	}
-#endif
-
 	username = get_user_name_or_exit(progname);
 
 	return pg_strdup(username);
diff -Naur postgresql-12.0.orig/src/bin/pg_ctl/pg_ctl.c postgresql-12.0/src/bin/pg_ctl/pg_ctl.c
--- postgresql-12.0.orig/src/bin/pg_ctl/pg_ctl.c	2019-09-30 16:06:55.000000000 -0400
+++ postgresql-12.0/src/bin/pg_ctl/pg_ctl.c	2019-10-03 09:17:39.673228477 -0400
@@ -2302,6 +2302,7 @@
 	/*
 	 * Disallow running as root, to forestall any possible security holes.
 	 */
+/*
 #ifndef WIN32
 	if (geteuid() == 0)
 	{
@@ -2313,7 +2314,7 @@
 		exit(1);
 	}
 #endif
-
+*/
 	env_wait = getenv("PGCTLTIMEOUT");
 	if (env_wait != NULL)
 		wait_seconds = atoi(env_wait);
diff -Naur postgresql-12.0.orig/src/bin/pg_upgrade/option.c postgresql-12.0/src/bin/pg_upgrade/option.c
--- postgresql-12.0.orig/src/bin/pg_upgrade/option.c	2019-09-30 16:06:55.000000000 -0400
+++ postgresql-12.0/src/bin/pg_upgrade/option.c	2019-10-03 09:18:52.009709973 -0400
@@ -98,8 +98,8 @@
 	}
 
 	/* Allow help and version to be run as root, so do the test here. */
-	if (os_user_effective_id == 0)
-		pg_fatal("%s: cannot be run as root\n", os_info.progname);
+	//if (os_user_effective_id == 0)
+	//	pg_fatal("%s: cannot be run as root\n", os_info.progname);
 
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
		PGBENCH_ARGS=\"-j \$NUM_CPU_PHYSICAL_CORES -c \$((\$NUM_CPU_PHYSICAL_CORES*4))\"
	;;
	\"HEAVY_CONTENTION\")
		PGBENCH_ARGS=\"-j \$((\$NUM_CPU_PHYSICAL_CORES*2)) -c \$((\$NUM_CPU_PHYSICAL_CORES*16))\"
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
