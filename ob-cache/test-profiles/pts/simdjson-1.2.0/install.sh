#!/bin/sh

rm -rf simdjson-0.8.2
tar -xf simdjson-0.8.2.tar.gz
cd simdjson-0.8.2
mkdir build
cd build

cmake .. -DCMAKE_BUILD_TYPE=Release -DSIMDJSON_JUST_LIBRARY=OFF
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd simdjson-0.8.2/build/benchmark
./bench_ondemand --benchmark_min_time=30 --benchmark_filter=\$@\<simdjson_ondemand\> > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > simdjson
chmod +x simdjson
