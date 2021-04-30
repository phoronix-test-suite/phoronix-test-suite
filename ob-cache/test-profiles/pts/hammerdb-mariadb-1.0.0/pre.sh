#!/bin/sh

export LD_LIBRARY_PATH=\$HOME/mysql_/lib/:$LD_LIBRARY_PATH

# SETUP
cd $HOME/mysql_
rm -rf .data/*
./scripts/mysql_install_db --no-defaults --user=`basename $DEBUG_REAL_HOME` --basedir=$HOME/mysql_ --ldata=$HOME/mysql_/.data
chmod -R 777 .data

# START SERVER
cd $HOME/mysql_
if [ "$(whoami)" == "root" ] ; then
    $HOME/mysql_/bin/mysqld_safe --no-defaults --user=root --datadir=$HOME/mysql_/.data &
else
    $HOME/mysql_/bin/mysqld_safe --no-defaults --datadir=$HOME/mysql_/.data &
fi

sleep 5

$HOME/mysql_/bin/mysqladmin -u `basename $DEBUG_REAL_HOME` password 'phoronix'

cd ~/HammerDB-4.0/

echo "puts \"SETTING CONFIGURATION\"
dbset db mysql
diset connection mysql_host localhost
diset connection mysql_port 3306
diset tpcc mysql_count_ware $2
diset tpcc mysql_partition true
diset tpcc mysql_num_vu $1
diset tpcc mysql_storage_engine innodb
diset tpcc mysql_user `basename $DEBUG_REAL_HOME`
diset tpcc mysql_pass phoronix
print dict
buildschema
waittocomplete" > schemabuild.tcl

./hammerdbcli auto schemabuild.tcl
