#!/bin/sh

tar -zxvf openssl-1.0.1g.tar.gz

cd openssl-1.0.1g/
./config no-zlib
make
echo \$? > ~/install-exit-status
cd ~

echo "#!/bin/sh
./openssl-1.0.1g/apps/openssl speed rsa4096 -multi \$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > openssl
chmod +x openssl


