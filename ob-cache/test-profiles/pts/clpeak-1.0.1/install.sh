#!/bin/sh

unzip -o clpeak-20190116.zip
cd clpeak-master
mkdir build
cd build

cmake ..
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~

echo "#!/bin/sh
cd clpeak-master/build
./clpeak \$@ > \$LOG_FILE 2>&1
echo $? > ~/test-exit-status" > clpeak
chmod +x clpeak


