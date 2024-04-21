#!/bin/bash
# START SERVER
cd $HOME/mysql_
RAM8P="$(($SYS_MEMORY * 75 / 100))"
if [ "$(whoami)" == "root" ] ; then
    $HOME/mysql_/bin/mariadbd-safe --no-defaults --innodb-log-file-size=1G --innodb-buffer-pool-size=${RAM8P}M --query-cache-size=64M --max_connections=8200 --max-heap-table-size=512M --tmp-table-size=2G --user=root --datadir=$HOME/mysql_/.data &
else
    $HOME/mysql_/bin/mariadbd-safe --no-defaults --innodb-log-file-size=1G --innodb-buffer-pool-size=${RAM8P}M --query-cache-size=64M --max_connections=8200 --max-heap-table-size=512M --tmp-table-size=2G --datadir=$HOME/mysql_/.data &
fi
sleep 5
$HOME/mysql_/bin/mariadb-admin -u `basename $DEBUG_REAL_HOME` password 'phoronix'
cd $HOME
tar -xf employees_db-full-1.0.6.tar.bz2
cd employees_db
../mysql_/bin/mariadb -h localhost -u `basename $DEBUG_REAL_HOME` -pphoronix -t < employees.sql
