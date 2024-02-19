#!/bin/sh
tar -xf duckdb-0.9.1.tar.gz
cd duckdb-0.9.1
BUILD_HTTPFS=1 BUILD_BENCHMARK=1 BUILD_TPCH=1 make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd duckdb-0.9.1
./build/release/benchmark/benchmark_runner --threads=\$NUM_CPU_CORES \"\$@/.*\" >\$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > duckdb
chmod +x duckdb
