#!/bin/bash
# START SERVER
cd $HOME/mariadb_
RAM8P="$(($SYS_MEMORY * 75 / 100))"
if [ "$(whoami)" == "root" ] ; then
    $HOME/mariadb_/bin/mariadbd-safe --no-defaults --innodb-log-file-size=1G --innodb-buffer-pool-size=${RAM8P}M --query-cache-size=64M --max_connections=800 --max-heap-table-size=512M --tmp-table-size=2G --user=root --datadir=$HOME/mariadb_/.data --max_prepared_stmt_count=90000 &
else
    $HOME/mariadb_/bin/mariadbd-safe --no-defaults --innodb-log-file-size=1G --innodb-buffer-pool-size=${RAM8P}M --query-cache-size=64M --max_connections=800 --max-heap-table-size=512M --tmp-table-size=2G --max_prepared_stmt_count=90000 --datadir=$HOME/mariadb_/.data &
fi
sleep 5
$HOME/mariadb_/bin/mariadb-admin -u `basename $DEBUG_REAL_HOME` password 'phoronix'
