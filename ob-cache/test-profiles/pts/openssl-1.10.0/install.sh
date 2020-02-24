#!/bin/sh

tar -zxvf openssl-1.1.0f.tar.gz

cd openssl-1.1.0f/
./config no-zlib
make -j $NUM_CPU_CORES
echo \$? > ~/install-exit-status
cd ~

echo "#!/bin/sh
LD_LIBRARY_PATH=openssl-1.1.0f/:\$LD_LIBRARY_PATH ./openssl-1.1.0f/apps/openssl speed -multi \$NUM_CPU_CORES rsa4096 > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > openssl
chmod +x openssl


