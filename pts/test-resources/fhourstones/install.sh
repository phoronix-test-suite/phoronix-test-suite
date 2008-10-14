#!/bin/sh

tar -xvf Fhourstones.tar.gz
make -j $NUM_CPU_JOBS

echo "#!/bin/sh
./SearchGame < inputs > \$LOG_FILE 2>&1" > fhourstones-benchmark
chmod +x fhourstones-benchmark
