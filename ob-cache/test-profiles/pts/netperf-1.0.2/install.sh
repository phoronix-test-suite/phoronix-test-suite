#!/bin/sh

tar -jxvf netperf-2.7.0.tar.bz2
cd netperf-2.7.0

if [ "X$CFLAGS_OVERRIDE" = "X" ]
then
          CFLAGS="$CFLAGS -O3 -march=native"
else
          CFLAGS="$CFLAGS_OVERRIDE"
fi

./configure CFLAGS="$CFLAGS"
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd netperf-2.7.0
./src/netperf -P 0 -v 0 \$@ | grep -v is  > \$LOG_FILE
echo \$? > ~/test-exit-status" > netperf
chmod +x netperf
