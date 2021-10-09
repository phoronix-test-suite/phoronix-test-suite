#!/bin/sh

rm -rf mysql_

# BUILD
tar -xf mariadb-10.6.4.tar.gz
mkdir ~/mysql_
cd mariadb-10.6.4/BUILD/
cmake -DCMAKE_INSTALL_PREFIX=$HOME/mysql_ -DCMAKE_BUILD_TYPE=Release ..
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
./scripts/mysql_install_db --no-defaults --user=`basename $DEBUG_REAL_HOME` --basedir=$HOME/mysql_ --ldata=$HOME/mysql_/.data

chmod -R 777 .data

cd ~
echo "#!/bin/sh
cd mysql_
./bin/mysqlslap --user=`basename $DEBUG_REAL_HOME` -pphoronix --host=localhost --verbose \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > mysqlslap
chmod +x mysqlslap
