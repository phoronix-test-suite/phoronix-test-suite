#!/bin/sh
tar -xf leveldb-1.23.tar.gz
cd leveldb-1.23
sed -i '4 a #include <ctime>' ./benchmarks/db_bench_sqlite3.cc
sed -i '299d' CMakeLists.txt
sed -i '304d' CMakeLists.txt
mkdir build
cd build
cmake  -DCMAKE_BUILD_TYPE=Release -DLEVELDB_BUILD_TESTS=OFF ..
make -j $NUM_CPU_THREADS
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd leveldb-1.23/build
./db_bench --threads=\$NUM_CPU_CORES \$@ > \$LOG_FILE
" > leveldb
chmod +x leveldb
