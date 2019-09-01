#!/bin/sh

tar -zxvf openssl-1.1.1.tar.gz

cd openssl-1.1.1/
./config no-zlib
make -j $NUM_CPU_CORES
echo \$? > ~/install-exit-status
cd ~

echo "#!/bin/sh
LD_LIBRARY_PATH=openssl-1.1.1/:\$LD_LIBRARY_PATH ./openssl-1.1.1/apps/openssl speed -multi \$NUM_CPU_CORES rsa4096 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > openssl
chmod +x openssl


