#!/bin/sh
rm -rf mariadb_
tar -xf mariadb-11.5.0.tar.gz
mkdir ~/mariadb_
cd mariadb-11.5.0/BUILD/
cmake -DCMAKE_INSTALL_PREFIX=$HOME/mariadb_ -DCMAKE_BUILD_TYPE=Release -DWITHOUT_ROCKSDB=1 ..
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
else
	make -j $NUM_CPU_CORES
fi
export PATH=$HOME/mariadb_/bin/:$HOME/mariadb-11.5.0/BUILD/extra/:$PATH
make install
echo $? > ~/install-exit-status

cd ~/mariadb_
mkdir .data
chmod -R 777 .data
RAM8P="$(($SYS_MEMORY * 75 / 100))"
./scripts/mariadb-install-db --no-defaults --user=`basename $DEBUG_REAL_HOME` --innodb-log-file-size=1G --innodb-buffer-pool-size=${RAM8P}M --query-cache-size=64M --max-heap-table-size=512M --tmp-table-size=2G --basedir=$HOME/mariadb_ --ldata=$HOME/mariadb_/.data
if [ "$(whoami)" == "root" ] ; then
    ./bin/mariadbd-safe --no-defaults --innodb-log-file-size=1G --innodb-buffer-pool-size=${RAM8P}M --query-cache-size=64M --max_connections=8200 --max-heap-table-size=512M --tmp-table-size=2G --user=root --datadir=$HOME/mariadb_/.data &
else
   ./bin/mariadbd-safe --no-defaults --innodb-log-file-size=1G --innodb-buffer-pool-size=${RAM8P}M --query-cache-size=64M --max_connections=8200 --max-heap-table-size=512M --tmp-table-size=2G --datadir=$HOME/mariadb_/.data &
fi
sleep 5
./bin/mariadb-admin -u `basename $DEBUG_REAL_HOME` password 'phoronix'
sleep 1
echo "DROP DATABASE sbtest;" | ./bin/mariadb -h localhost -u `basename $DEBUG_REAL_HOME` -pphoronix --comments
echo "CREATE DATABASE sbtest;" |  ./bin/mariadb -h localhost -u `basename $DEBUG_REAL_HOME` -pphoronix --comments
sysbench oltp_common --threads=$NUM_CPU_CORES --rand-type=uniform --db-driver=mysql --mysql-db=sbtest --mysql-host=localhost --mysql-port=3306 --mysql-user=`basename $DEBUG_REAL_HOME` --mysql-socket=/tmp/mysql.sock --mysql-password=phoronix prepare --tables=16 --table-size=1000000
./bin/mariadb-dump -h localhost -u `basename $DEBUG_REAL_HOME` -pphoronix --comments sbtest > ~/mysql-dumped
echo $? > ~/install-exit-status
echo "DROP DATABASE sbtest;" |  ./bin/mariadb -h localhost -u `basename $DEBUG_REAL_HOME` -pphoronix --comments

./bin/mariadb-admin -u `basename $DEBUG_REAL_HOME` -pphoronix shutdown
sleep 3
cd ~
echo "#!/bin/sh
cd mariadb_
echo \"DROP DATABASE sbtest;\" |  ./bin/mariadb --comments -h localhost -u \`basename $DEBUG_REAL_HOME\` -pphoronix
echo \"CREATE DATABASE sbtest;\" |  ./bin/mariadb --comments -h localhost -u \`basename $DEBUG_REAL_HOME\` -pphoronix
./bin/mariadb --comments -h localhost -u \`basename $DEBUG_REAL_HOME\` -pphoronix sbtest < ~/mysql-dumped 
sleep 3
sysbench \$1 --threads=\$2 --time=200 --rand-type=uniform --db-driver=mysql --mysql-db=sbtest --mysql-host=localhost --mysql-user=\`basename $DEBUG_REAL_HOME\` --mysql-password=phoronix  --mysql-socket=/tmp/mysql.sock run --tables=16 --table-size=1000000 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
echo \"DROP DATABASE sbtest;\" | ./bin/mariadb --comments -h localhost -u \`basename $DEBUG_REAL_HOME\` -pphoronix" > mariadb
chmod +x mariadb
