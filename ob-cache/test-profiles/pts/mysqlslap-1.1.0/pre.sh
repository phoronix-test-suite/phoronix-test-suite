#!/bin/sh

# START SERVER
cd $HOME/mysql_
if [ "$(whoami)" == "root" ] ; then
    $HOME/mysql_/bin/mysqld_safe --user=root --no-defaults --datadir=$HOME/mysql_/.data &
else
    $HOME/mysql_/bin/mysqld_safe --no-defaults --datadir=$HOME/mysql_/.data &
fi

sleep 5

$HOME/mysql_/bin/mysqladmin -u `basename $DEBUG_REAL_HOME` password 'phoronix'

cd $HOME
tar -xf employees_db-full-1.0.6.tar.bz2
cd employees_db
../mysql_/bin/mysql -h localhost -u `basename $DEBUG_REAL_HOME` -pphoronix -t < employees.sql 
