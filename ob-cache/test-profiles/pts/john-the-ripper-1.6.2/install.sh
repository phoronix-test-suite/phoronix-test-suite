#!/bin/sh

tar -zxvf john-1.8.0-jumbo-1.tar.gz
cd john-1.8.0-jumbo-1/src/
CFLAGS="-O3 -march=native $CFLAGS -std=gnu89" ./configure --disable-native-tests --disable-opencl
CFLAGS="-O3 -march=native $CFLAGS -std=gnu89" make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~/
echo "#!/bin/sh
cd john-1.8.0-jumbo-1/run/
./john \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > john-the-ripper
chmod +x john-the-ripper
