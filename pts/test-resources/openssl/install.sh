#!/bin/sh

tar -zxvf openssl-0.9.8k.tar.gz

mkdir openssl_/

cd openssl-0.9.8k/
./config --prefix=$HOME/openssl_/ no-zlib
make
echo \$? > ~/test-exit-status
make install
cd ..
rm -rf openssl-0.9.8k/
rm -rf openssl_/lib/

echo "#!/bin/sh
./openssl_/bin/openssl speed rsa4096 -multi \$NUM_CPU_CORES > \$LOG_FILE 2>&1
echo \$? > ~/test-exit-status" > openssl
chmod +x openssl


