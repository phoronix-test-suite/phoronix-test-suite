#!/bin/sh

tar -jxvf tiobench-20170504.tar.bz2
cd tiobench-20170504

make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status
cd ~

echo "#!/bin/sh
cd tiobench-20170504/
./tiotest \$@ > \$LOG_FILE 2>&1" > tiobench
chmod +x tiobench
