#!/bin/sh

tar -xjf apitest-20140726.tar.bz2
cd apitest-master
mkdir out
cd out
cmake -G "Unix Makefiles" -DCMAKE_BUILD_TYPE=Release -DHAVE_LIBUDEV_H=0  ..
make -j $NUM_CPU_JOBS
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd apitest-master/bin/
./apitest \$@ > \$LOG_FILE 2>&1
echo $? > ~/test-exit-status" > apitest
chmod +x apitest
