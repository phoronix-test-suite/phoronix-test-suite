#!/bin/sh

tar -xzf redis-5.0.5.tar.gz

cd ~/redis-5.0.5/deps
make hiredis jemalloc linenoise lua

cd ~/redis-5.0.5
make MALLOC=libc -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
tar -xf memtier_benchmark-1.2.17.tar.gz
cd memtier_benchmark-1.2.17/
autoreconf -ivf
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd ~/redis-5.0.5/

./src/redis-server &
REDIS_SERVER_PID=\$!
sleep 10

cd ~/memtier_benchmark-1.2.17/
./memtier_benchmark --hide-histogram -t \$NUM_CPU_CORES \$@ > \$LOG_FILE
kill \$REDIS_SERVER_PID" > memtier-benchmark
chmod +x memtier-benchmark
