#!/bin/sh

tar -xf apache-cassandra-4.0.0-bin.tar.gz
cd ~/apache-cassandra-4.0.0/bin/
chmod +x cassandra
cd ~/apache-cassandra-4.0.0/tools/bin
chmod +x cassandra-stress

cd ~/apache-cassandra-4.0.0/conf
sed -i '/-XX:GCLogFileSize=10M/d' ./jvm.options
sed -i '/-XX:NumberOfGCLogFiles=10/d' ./jvm.options
sed -i '/-XX:+UseGCLogFileRotation/d' ./jvm.options
sed -i '/-XX:+PrintPromotionFailure/d' ./jvm.options
sed -i '/-XX:+PrintGCApplicationStoppedTime/d' ./jvm.options
sed -i '/-XX:+PrintTenuringDistribution/d' ./jvm.options
sed -i '/-XX:+PrintHeapAtGC/d' ./jvm.options
sed -i '/-XX:+PrintGCDateStamps/d' ./jvm.options
sed -i '/-XX:+UseParNewGC/d' ./jvm.options
sed -i '/-XX:ThreadPriorityPolicy=42/d' ./jvm.options
# sed -i '//d' ./infile
mkdir ~/apache-cassandra-4.0.0/logs

cd ~
echo "#!/bin/bash
cd ~/apache-cassandra-4.0.0/tools/bin
case \"\$1\" in
\"WRITE\")
	./cassandra-stress write duration=2m -rate threads=\$NUM_CPU_CORES > \$LOG_FILE 2>&1
	;;
\"READ\")
	./cassandra-stress write -rate threads=\$NUM_CPU_CORES
	sleep 2
	./cassandra-stress read duration=2m -rate threads=\$NUM_CPU_CORES > \$LOG_FILE 2>&1
	;;
\"MIXED_1_1\")
	./cassandra-stress write -rate threads=\$NUM_CPU_CORES
	sleep 2
	./cassandra-stress mixed ratio\(write=1,read=1\) duration=2m -rate threads=\$NUM_CPU_CORES > \$LOG_FILE 2>&1
	;;
\"MIXED_1_3\")
	./cassandra-stress write -rate threads=\$NUM_CPU_CORES
	sleep 2
	./cassandra-stress mixed ratio\(write=1,read=3\) duration=90s -rate threads=\$NUM_CPU_CORES > \$LOG_FILE 2>&1
	;;
esac
" > cassandra
chmod +x cassandra
