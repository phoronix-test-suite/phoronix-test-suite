#!/bin/sh

unzip -o sockperf-3.4.zip
cd sockperf-3.4
./autogen.sh
./configure
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd sockperf-3.4

./sockperf server &
sleep 5

./sockperf \$@ >\$LOG_FILE 2>&1
echo \$? > ~/install-exit-status

killall -9 sockperf" > sockperf
chmod +x sockperf
