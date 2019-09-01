#!/bin/sh

unzip -o sysbench-20180728.zip
cd sysbench-master
./autogen.sh
./configure  --without-mysql
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd sysbench-master
./src/sysbench --threads=\$NUM_CPU_CORES \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > sysbench
chmod +x sysbench
