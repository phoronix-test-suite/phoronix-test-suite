#!/bin/sh
rm -rf tidb-community-server
if [ $OS_ARCH = "aarch64" ]
then
	tar -xf tidb-community-server-v7.3.0-linux-arm64.tar.gz
	mv tidb-community-server-v7.3.0-linux-arm64/ tidb-community-server
elif [ $OS_ARCH = "x86_64" ]
then
	tar -xf tidb-community-server-v7.3.0-linux-amd64.tar.gz
	mv tidb-community-server-v7.3.0-linux-amd64/ tidb-community-server
else
	echo "ERROR: Not a supported platform..." > $LOG_FILE
	echo 2 > ~/install-exit-status
	exit 2
fi
cd tidb-community-server
./local_install.sh
source $HOME/.profile
export PATH=$HOME/.tiup/bin:$PATH
mkdir -p $DEBUG_REAL_HOME/.tiup/bin/
cp -f $HOME/.tiup/bin/root.json $DEBUG_REAL_HOME/.tiup/bin/
tiup install v7.3.0
tiup playground v7.3.0 &
TIUP_PID=$!
sleep 15
echo "DROP DATABASE sbtest;" |  mysql --comments --host 127.0.0.1 --port 4000 -u root
echo "CREATE DATABASE sbtest;" |  mysql --comments --host 127.0.0.1 --port 4000 -u root
sysbench oltp_common --threads=$NUM_CPU_CORES --rand-type=uniform --db-driver=mysql --mysql-db=sbtest --mysql-host=127.0.0.1 --mysql-port=4000 --mysql-user=root prepare --tables=16 --table-size=1000000
mysqldump sbtest --host 127.0.0.1 --port 4000 -u root > ~/mysql-dumped
echo $? > ~/install-exit-status
echo "DROP DATABASE sbtest;" |  mysql --comments --host 127.0.0.1 --port 4000 -u root
kill -9 $TIUP_PID
killall -9 tidb-server
killall -9 tikv-server
killall -9 tiup-playground

cd ~
echo "#!/bin/sh
export PATH=\$HOME/.tiup/bin:\$PATH
tiup clean --all
tiup playground v7.3.0 &
TIUP_PID=\$!
sleep 10

echo \"DROP DATABASE sbtest;\" |  mysql --comments --host 127.0.0.1 --port 4000 -u root
echo \"CREATE DATABASE sbtest;\" |  mysql --comments --host 127.0.0.1 --port 4000 -u root
mysql  --host 127.0.0.1 --port 4000 -u root sbtest < ~/mysql-dumped 
sleep 3

sysbench \$1 --threads=\$2 --time=300 --rand-type=uniform --db-driver=mysql --mysql-db=sbtest --mysql-host=127.0.0.1 --mysql-port=4000 --mysql-user=root run --tables=16 --table-size=1000000 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

echo \"DROP DATABASE sbtest;\" |  mysql --comments --host 127.0.0.1 --port 4000 -u root
kill -9 \$TIUP_PID
killall -9 tidb-server
killall -9 tikv-server
killall -9 tiup-playground
tiup clean --all" > tidb
chmod +x tidb
