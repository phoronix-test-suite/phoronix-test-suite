#!/bin/sh

tar -xf sysbench-1.0.20.tar.gz
cd sysbench-1.0.20
./autogen.sh
./configure  --without-mysql
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~/

echo "#!/bin/sh
cd sysbench-1.0.20
./src/sysbench --threads=\$NUM_CPU_CORES --time=90 \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > sysbench
chmod +x sysbench
