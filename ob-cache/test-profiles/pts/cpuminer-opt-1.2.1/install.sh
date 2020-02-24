#!/bin/sh
# libcurl4-openssl-dev libssl-dev
unzip -o cpuminer-opt-3.8.8.1.zip
cd cpuminer-opt-3.8.8.1
./autogen.sh 
CFLAGS="-O3 -march=native $CFLAGS" ./configure --without-curl
make -j $NUM_CPU_CORES
echo $? > ~/install-exit-status

cd ~
echo "#!/bin/sh
cd cpuminer-opt-3.8.8.1
./cpuminer --quiet --time-limit=30 --benchmark \$@ > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > cpuminer-opt
chmod +x cpuminer-opt
