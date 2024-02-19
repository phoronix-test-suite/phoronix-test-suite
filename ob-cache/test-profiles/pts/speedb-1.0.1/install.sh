#!/bin/sh
tar -xf speedb-speedb-v2.7.0.tar.gz
cd speedb-speedb-v2.7.0
mkdir build
cd build
export CFLAGS="-O3 -march=native -Wno-error=maybe-uninitialized -Wno-error=uninitialized -Wno-error=deprecated-copy -Wno-error=pessimizing-move $CFLAGS"
export CXXFLAGS="-O3 -march=native -Wno-error=maybe-uninitialized -Wno-error=uninitialized -Wno-error=deprecated-copy -Wno-error=pessimizing-move $CXXFLAGS"
cmake -DCMAKE_BUILD_TYPE=Release -DWITH_SNAPPY=ON ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/bash
rm -rf /tmp/rocksdbtest-1000/dbbench/
cd speedb-speedb-v2.7.0/build/
./db_bench \$@ --threads \$NUM_CPU_CORES --duration 60 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
rm -rf /tmp/rocksdbtest-1000/dbbench/" > speedb
chmod +x speedb
