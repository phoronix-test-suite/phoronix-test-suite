#!/bin/sh

tar -xf sockperf-3.7.tar.gz
cd sockperf-3.7
./autogen.sh
./configure
if [ "$OS_TYPE" = "BSD" ]
then
	gmake -j $NUM_CPU_CORES
else
	make -j $NUM_CPU_CORES
fi
echo $? > ~/install-exit-status
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd sockperf-3.7

./sockperf server &
sleep 5

./sockperf \$@ >\$LOG_FILE 2>&1
echo \$? > ~/test-exit-status

killall -9 sockperf" > sockperf
chmod +x sockperf
