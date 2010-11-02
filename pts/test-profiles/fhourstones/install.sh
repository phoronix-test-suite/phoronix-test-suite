#!/bin/sh

tar -zxvf Fhourstones.tar.gz
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status

echo "#!/bin/sh
./SearchGame < inputs > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > fhourstones-benchmark
chmod +x fhourstones-benchmark
