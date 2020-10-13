#!/bin/sh

tar -xvjf cairo-demos-20120130.tar.bz2
cd cairo-demos/
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd cairo-demos/
./\$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > cairo-perf-demos
chmod +x cairo-perf-demos
