#!/bin/sh

tar -xf rocksdb-7.0.1.tar.gz
cd rocksdb-7.0.1
mkdir build
cd build
export CFLAGS="-O3 -march=native $CFLAGS"
export CXXFLAGS="-O3 -march=native $CXXFLAGS"
cmake -DCMAKE_BUILD_TYPE=Release -DWITH_SNAPPY=ON  ..
make -j $NUM_CPU_CORES
make db_bench
echo $? > ~/install-exit-status

if [[ ! -x db_bench ]]
then
	# Unfortunately older GCC will emit error from these no-error 
	echo "TRYING AGAIN WITH NO-ERROR BITS"
	rm -rf ~/rocksdb-7.0.1/build/*
	export CFLAGS="-O3 -march=native -Wno-error=deprecated-copy -Wno-error=pessimizing-move $CFLAGS"
	export CXXFLAGS="-O3 -march=native -Wno-error=deprecated-copy -Wno-error=pessimizing-move $CXXFLAGS"
	cmake -DCMAKE_BUILD_TYPE=Release -DWITH_SNAPPY=ON ..
	make -j $NUM_CPU_CORES
	make db_bench
	echo $? > ~/install-exit-status
fi

cd ~
echo "#!/bin/bash
rm -rf /tmp/rocksdbtest-1000/dbbench/

cd rocksdb-7.0.1/build/

./db_bench \$@ --threads \$NUM_CPU_CORES --duration 60 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status
" > rocksdb
chmod +x rocksdb
