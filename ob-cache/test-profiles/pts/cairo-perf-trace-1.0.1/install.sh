#!/bin/sh

tar -xvzf cairo-1.10.2.tar.gz
cd cairo-1.10.2/
./configure --enable-svg=no
make -j $NUM_CPU_JOBS
cd perf/
make cairo-perf-trace
echo $? > ~/install-exit-status
cd ../..
tar -xvjf cairo-traces-20120129.tar.bz2

echo "#!/bin/sh
./cairo-1.10.2/perf/cairo-perf-trace -v -i 1 cairo-traces-201201129/\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > cairo-perf-trace
chmod +x cairo-perf-trace
