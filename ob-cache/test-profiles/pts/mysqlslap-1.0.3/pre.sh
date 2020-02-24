#!/bin/sh

# START SERVER
cd $HOME/mysql_
$HOME/mysql_/bin/mysqld_safe --datadir=$HOME/mysql_/.data &
sleep 5

$HOME/mysql_/bin/mysqladmin -u root password 'phoronix'

cd $HOME
tar -xf employees_db-full-1.0.6.tar.bz2
cd employees_db
../mysql_/bin/mysql -h localhost -u root -pphoronix -t < employees.sql 
