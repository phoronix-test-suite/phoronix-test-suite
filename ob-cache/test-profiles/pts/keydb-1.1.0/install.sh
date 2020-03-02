#!/bin/sh

tar -xzf KeyDB-5.3.1.tar.gz

cd ~/KeyDB-5.3.1/deps
make hiredis jemalloc linenoise lua

cd ~/KeyDB-5.3.1
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
cd ~/KeyDB-5.3.1/
./src/keydb-server --server-threads 4 &
KEYDB_SERVER_PID=\$!
sleep 8
cd ~/memtier_benchmark-1.2.17/
./memtier_benchmark --hide-histogram -t \$NUM_CPU_CORES \$@ > \$LOG_FILE
kill \$KEYDB_SERVER_PID
sleep 2" > keydb
chmod +x keydb
