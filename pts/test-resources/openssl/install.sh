#!/bin/sh

tar -xvf openssl-0.9.8k.tar.gz
cd openssl-0.9.8k/
./config no-zlib
make
echo \$? > ~/test-exit-status
cd ..

echo "#!/bin/sh
cd openssl-0.9.8k/
./apps/openssl speed rsa4096 -multi \$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > openssl
chmod +x openssl


