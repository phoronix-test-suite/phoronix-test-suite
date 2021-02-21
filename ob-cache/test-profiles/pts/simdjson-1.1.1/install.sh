#!/bin/sh

rm -rf simdjson-0.7.1
tar -xf simdjson-0.7.1.tar.gz
cd simdjson-0.7.1
mkdir build
cd build

cmake .. -DCMAKE_BUILD_TYPE=Release -DSIMDJSON_JUST_LIBRARY=OFF
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd simdjson-0.7.1/build/benchmark
./bench_ondemand --benchmark_min_time=30 --benchmark_filter=\$@\<OnDemand\> > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > simdjson
chmod +x simdjson
