#!/bin/sh
rm -rf KeyDB-6.3.2
tar -xf KeyDB-6.3.2.tar.gz
cd ~/KeyDB-6.3.2/deps
make hiredis jemalloc linenoise lua
cd ~/KeyDB-6.3.2
make MALLOC=libc -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd ~/KeyDB-6.3.2/
./src/keydb-server --server-threads 4 &
KEYDB_SERVER_PID=\$!
sleep 5
./src/keydb-benchmark --threads \$NUM_CPU_CORES \$@ > \$LOG_FILE 2>&1
kill -9 \$KEYDB_SERVER_PID
sleep 2" > keydb
chmod +x keydb
