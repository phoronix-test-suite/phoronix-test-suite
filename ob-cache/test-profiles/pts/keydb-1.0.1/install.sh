#!/bin/sh

tar -xzf KeyDB-5.0.2.tar.gz

cd ~/KeyDB-5.0.2/deps
make hiredis jemalloc linenoise lua

cd ~/KeyDB-5.0.2
make MALLOC=libc -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd ~/KeyDB-5.0.2/

./src/keydb-server --server-threads 4 &
KEYDB_SERVER_PID=\$!
sleep 8

./src/keydb-benchmark --threads \$NUM_CPU_CORES \$@ > \$LOG_FILE
kill \$KEYDB_SERVER_PID
sleep 2" > keydb
chmod +x keydb
