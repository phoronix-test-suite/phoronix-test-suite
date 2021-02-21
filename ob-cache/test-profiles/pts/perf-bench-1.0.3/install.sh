#!/bin/sh

tar -xf linux-5.9.tar.xz
cd linux-5.9/tools/perf
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cp perf ~

cd ~
rm -rf linux-5.9

echo "#!/bin/sh
./perf bench \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > perf-bench

chmod +x perf-bench
