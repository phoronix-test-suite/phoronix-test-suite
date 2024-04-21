#!/bin/bash
rm -rf mysql_
# BUILD
tar -xf mariadb-11.5.0.tar.gz
mkdir ~/mysql_
cd mariadb-11.5.0/BUILD/
cmake -DCMAKE_INSTALL_PREFIX=$HOME/mysql_ -DCMAKE_BUILD_TYPE=Release -DWITHOUT_ROCKSDB=1 ..
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
else
	make -j $NUM_CPU_CORES
fi
make install
echo $? > ~/install-exit-status
# SETUP
cd ~/mysql_
rm -rf mariadb-11.5.0
RAM8P="$(($SYS_MEMORY * 75 / 100))"
./scripts/mysql_install_db --no-defaults --user=`basename $DEBUG_REAL_HOME` --innodb-log-file-size=1G --innodb-buffer-pool-size=${RAM8P}M --query-cache-size=64M --max-heap-table-size=512M --tmp-table-size=2G --basedir=$HOME/mysql_ --ldata=$HOME/mysql_/.data
chmod -R 777 .data
cd ~
echo "#!/bin/sh
cd mysql_
./bin/mysqlslap --user=`basename $DEBUG_REAL_HOME` -pphoronix --host=localhost --verbose \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mysqlslap
chmod +x mysqlslap
