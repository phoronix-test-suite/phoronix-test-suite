#!/bin/sh

tar -xzf redis-6.2.6.tar.gz

cd ~/redis-6.2.6/deps
make hiredis jemalloc linenoise lua

cd ~/redis-6.2.6
make MALLOC=libc -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
tar -xf memtier_benchmark-1.3.0.tar.gz
cd memtier_benchmark-1.3.0/
autoreconf -ivf
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd ~/redis-6.2.6/

./src/redis-server &
REDIS_SERVER_PID=\$!
sleep 10

cd ~/memtier_benchmark-1.3.0/
./memtier_benchmark --hide-histogram -t \$NUM_CPU_CORES \$@ > \$LOG_FILE
kill \$REDIS_SERVER_PID" > memtier-benchmark
chmod +x memtier-benchmark
