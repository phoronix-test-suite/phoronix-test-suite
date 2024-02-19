#!/bin/sh
rm -rf scylla
if [ $OS_ARCH = "aarch64" ]
then
	tar -xf scylla-5.2.9-0.20230920.5709d0043978.aarch64.tar.gz
elif [ $OS_ARCH = "x86_64" ]
then
	tar -xf scylla-5.2.9-0.20230920.5709d0043978.x86_64.tar.gz
else
	echo "ERROR: Not a supported platform..." > $LOG_FILE
	echo 2 > ~/install-exit-status
	exit 2
fi
mv scylla scylla-install

tar -xf apache-cassandra-4.1.3-bin.tar.gz
cd ~/apache-cassandra-4.1.3/tools/bin
chmod +x cassandra-stress
mkdir ~/apache-cassandra-4.1.3/logs
cd ~
pip3 install --user traceback-with-variables
cd scylla-install
mkdir ~/scylla
./install.sh  --prefix $HOME/scylla --python3 /usr/bin/python3 --nonroot
echo $? > ~/install-exit-status
cd ~/scylla
mkdir conf
cp etc/scylla/scylla.yaml conf/scylla.yaml
cd ~
rm -rf scylla-install
echo "#!/bin/bash
cd scylla
rm -rf tmp
./bin/scylla --workdir tmp --smp \$NUM_CPU_CORES --developer-mode 1 > ~/scylla-server-log 2>&1 &
SCYLLA_SERVER_PID=\$!
sleep 5
grep -q \"initialization completed.\" <(tail -f ~/scylla-server-log)
sleep 1
cd ~/apache-cassandra-4.1.3/tools/bin
case \"\$1\" in
\"WRITE\")
	./cassandra-stress write duration=2m -rate threads=\$NUM_CPU_CORES -mode native cql3 protocolVersion=4 > \$LOG_FILE 2>&1
	;;
\"READ\")
	./cassandra-stress write -rate threads=\$NUM_CPU_CORES -mode native cql3 protocolVersion=4
	sleep 2
	./cassandra-stress read duration=2m -rate threads=\$NUM_CPU_CORES -mode native cql3 protocolVersion=4 > \$LOG_FILE 2>&1
	;;
\"MIXED_1_1\")
	./cassandra-stress write -rate threads=\$NUM_CPU_CORES -mode native cql3 protocolVersion=4
	sleep 2
	./cassandra-stress mixed ratio\(write=1,read=1\) duration=2m -rate threads=\$NUM_CPU_CORES -mode native cql3 protocolVersion=4 > \$LOG_FILE 2>&1
	;;
\"MIXED_1_3\")
	./cassandra-stress write -rate threads=\$NUM_CPU_CORES -mode native cql3 protocolVersion=4
	sleep 2
	./cassandra-stress mixed ratio\(write=1,read=3\) duration=90s -rate threads=\$NUM_CPU_CORES -mode native cql3 protocolVersion=4 > \$LOG_FILE 2>&1
	;;
esac

sleep 3
kill -9 \$SCYLLA_SERVER_PID
sleep 2
rm -rf ~/scylla/tmp" > scylladb
chmod +x scylladb
