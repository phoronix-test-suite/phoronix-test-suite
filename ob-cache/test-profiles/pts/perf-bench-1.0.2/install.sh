#!/bin/sh

tar -xf linux-5.9-rc3.tar.gz
cd linux-5.9-rc3/tools/perf
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cp perf ~

cd ~
rm -rf linux-5.9-rc3

echo "#!/bin/sh
./perf bench \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > perf-bench

chmod +x perf-bench
