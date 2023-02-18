#!/bin/sh

if which redis-server > /dev/null 2>&1 ;
then
	echo 0 > ~/install-exit-status
else
	echo "ERROR: Redis server is not found on the system! No redis-server found in PATH."
	echo 2 > ~/install-exit-status
fi

tar -xzf memtier_benchmark-1.3.0.tar.gz
cd memtier_benchmark-1.3.0
autoreconf -ivf
./configure
make -j $NUM_CPU_CORES
cd $HOME

echo "#!/bin/bash
redis-server &
REDIS_SERVER_PID=\$!
sleep 15
if [[ \"\$1\" == \"memtier\" ]]; then
    cd memtier_benchmark-1.3.0
    ./memtier_benchmark --ratio=\$2 -d 1024 --pipeline=8 --test-time=90 --key-pattern=R:R --key-minimum=1 --key-maximum=10000000 -c 10 -t 8 -s 127.0.0.1 -p 6379 --out-file=\$LOG_FILE
else
    redis-benchmark -t \$2 -n 10000000 -P 16 --csv > \$LOG_FILE 2>&1
fi
kill \$REDIS_SERVER_PID
redis-server --version > ~/pts-footnote 2>/dev/null
sleep 30
rm -f dump.rdb
sed \"s/\\\"/ /g\" -i \$LOG_FILE
if [[ \"\$3\" == \"lpop\" ]]; then
    sed -i '1d' \$LOG_FILE
else
    sed -i '2d' \$LOG_FILE
fi" > redis
chmod +x redis
