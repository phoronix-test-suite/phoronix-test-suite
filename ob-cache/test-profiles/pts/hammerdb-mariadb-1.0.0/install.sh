#!/bin/sh

rm -rf mysql_

# BUILD
tar -xf mariadb-10.5.9.tar.gz
mkdir ~/mysql_
cd mariadb-10.5.9/BUILD/
cmake -DCMAKE_INSTALL_PREFIX=$HOME/mysql_ -DDEFAULT_SYSCONFDIR=$HOME ..
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
	echo $? > ~/install-exit-status
	gmake install
else
	make -j $NUM_CPU_CORES
	echo $? > ~/install-exit-status
	make install
fi

# SETUP
# Moved to pre process to wipe .data each time
#cd ~/mysql_
#./scripts/mysql_install_db --no-defaults --user=`basename $DEBUG_REAL_HOME` --basedir=$HOME/mysql_ --ldata=$HOME/mysql_/.data
#chmod -R 777 .data

cd ~
tar -xf HammerDB-4.0-Linux.tar.gz

echo "[mysqld]
skip-log-bin
datadir=/data
default_authentication_plugin=mysql_native_password
socket=/tmp/mysql.sock
port=3306
bind_address=localhost
# general
max_connections=4000
table_open_cache=8000
table_open_cache_instances=16
back_log=1500
default_password_lifetime=0
ssl=0
performance_schema=OFF
max_prepared_stmt_count=128000
skip_log_bin=1
character_set_server=latin1
collation_server=latin1_swedish_ci
transaction_isolation=REPEATABLE-READ
# files
innodb_file_per_table
innodb_log_file_size=1024M
innodb_log_files_in_group=32
innodb_open_files=4000
# buffers
innodb_buffer_pool_size=64000M
innodb_buffer_pool_instances=16
innodb_log_buffer_size=64M
# tune
innodb_page_size=8192
innodb_doublewrite=0
innodb_thread_concurrency=0
innodb_flush_log_at_trx_commit=0
innodb_max_dirty_pages_pct=90
innodb_max_dirty_pages_pct_lwm=10
join_buffer_size=32K
sort_buffer_size=32K
innodb_use_native_aio=1
innodb_stats_persistent=1
innodb_spin_wait_delay=6
innodb_max_purge_lag_delay=300000
innodb_max_purge_lag=0
innodb_flush_method=O_DIRECT_NO_FSYNC
innodb_checksum_algorithm=none
innodb_io_capacity=4000
innodb_io_capacity_max=20000
innodb_lru_scan_depth=9000
innodb_change_buffering=none
innodb_read_only=0
innodb_page_cleaners=4
innodb_undo_log_truncate=off
# perf special
innodb_adaptive_flushing=1
innodb_flush_neighbors=0
innodb_read_io_threads=16
innodb_write_io_threads=16
innodb_purge_threads=4
innodb_adaptive_hash_index=0
# monitoring
innodb_monitor_enable='%'" > my.cnf
ln -s my.cnf .my.cnf

echo "#!/bin/sh
cd HammerDB-4.0/
export LD_LIBRARY_PATH=\$HOME/mysql_/lib/:\$LD_LIBRARY_PATH

echo \"#vi mysqlrun.tcl
puts \\\"SETTING CONFIGURATION\\\"
dbset db mysql
diset connection mysql_host localhost
diset connection mysql_port 3306
diset tpcc mysql_driver timed
diset tpcc mysql_prepared false
diset tpcc mysql_rampup 2
diset tpcc mysql_duration 5
diset tpcc mysql_user `basename $DEBUG_REAL_HOME`
diset tpcc mysql_pass phoronix
vuset logtotemp 1
loadscript
puts \\\"TEST STARTED\\\"
vuset vu \$1
vucreate
vurun
runtimer 500
vudestroy
puts \\\"TEST COMPLETE\\\"\" > mysqlrun.tcl

./hammerdbcli auto mysqlrun.tcl > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > hammerdb-mariadb
chmod +x hammerdb-mariadb
