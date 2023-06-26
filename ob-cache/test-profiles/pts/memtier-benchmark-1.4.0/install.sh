#!/bin/sh

tar -xzf redis-7.0.4.tar.gz

cd ~/redis-7.0.4/deps
make hiredis jemalloc linenoise lua

cd ~/redis-7.0.4
make MALLOC=libc -j $NUM_CPU_CORES
retVal=$?
if [ $retVal -ne 0 ]; then
	echo $retVal > ~/install-exit-status
	exit $retVal
fi

cd ~
tar -xf memtier_benchmark-1.4.0.tar.gz
cd memtier_benchmark-1.4.0/
autoreconf -ivf
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd ~/redis-7.0.4/

echo \"io-threads \$NUM_CPU_PHYSICAL_CORES
io-threads-do-reads yes
tcp-keepalive 0\" > redis.conf

./src/redis-server redis.conf &
REDIS_SERVER_PID=\$!
sleep 6

cd ~/memtier_benchmark-1.4.0/
./memtier_benchmark --hide-histogram -t \$NUM_CPU_CORES \$@ > \$LOG_FILE
kill \$REDIS_SERVER_PID" > memtier-benchmark
chmod +x memtier-benchmark
