#!/bin/sh

rm -rf KeyDB-6.2.0
tar -xzf KeyDB-6.2.0.tar.gz

cd ~/KeyDB-6.2.0/deps
make hiredis jemalloc linenoise lua

cd ~/KeyDB-6.2.0
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
cd ~/KeyDB-6.2.0/
./src/keydb-server --server-threads 4 &
KEYDB_SERVER_PID=\$!
sleep 8
cd ~/memtier_benchmark-1.3.0/
./memtier_benchmark --hide-histogram -t \$NUM_CPU_CORES \$@ > \$LOG_FILE
kill \$KEYDB_SERVER_PID
sleep 2" > keydb
chmod +x keydb
