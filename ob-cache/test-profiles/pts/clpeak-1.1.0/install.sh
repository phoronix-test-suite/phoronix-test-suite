#!/bin/sh
tar -xf clpeak-1.1.2.tar.gz
cd clpeak-1.1.2
mkdir build
cd build
cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status
cd ~
echo "#!/bin/sh
cd clpeak-1.1.2/build
./clpeak \$@ > \$LOG_FILE 2>&1
echo $? > ~/test-exit-status" > clpeak
chmod +x clpeak
