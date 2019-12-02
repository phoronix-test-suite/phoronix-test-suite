#!/bin/sh

unzip -o postgresql-10.3-1-windows-x64-binaries.zip
rm -rf $HOME/pg_
mkdir -p $HOME/pg_/data/postgresql/extension/
touch $HOME/pg_/data/postgresql/extension/plpgsql.control

# initialize database with encoding and locale
cd pgsql/bin
./initdb.exe -D $HOME/db --encoding=SQL_ASCII --locale=C
cd ~

echo "If this test fails to run, you may need to manually install the Microsoft Visual C++ Redistributable package for MSVCR120.dll: https://www.microsoft.com/en-us/download/details.aspx?id=40784" > ~/install-message

echo "#!/bin/sh
PGDATA=\$HOME/db/
PGPORT=7777
export PGDATA
mkdir db
export PGPORT
# start server
./pgsql/bin/pg_ctl.exe start -o '-c autovacuum=false'
# wait for server to start
sleep 10

# create test db
./pgsql/bin/createdb.exe pgbench

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

./pgsql/bin/pgbench.exe -i -s \$SCALING_FACTOR pgbench

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
./pgsql/bin/pgbench.exe \$PGBENCH_ARGS \$PGBENCH_MORE_ARGS -T 60 pgbench >\$LOG_FILE
# drop test db
./pgsql/bin/dropdb.exe pgbench
# stop server
./pgsql/bin/pg_ctl.exe stop" > pgbench
chmod +x pgbench

