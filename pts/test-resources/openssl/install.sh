#!/bin/sh

cd $1

tar -xvf openssl-0.9.8g.tar.gz
cd openssl-0.9.8g/
./config no-zlib
make
cd ..

echo "#!/bin/sh
cd openssl-0.9.8g/
./apps/openssl speed rsa -multi \$NUM_CPU_CORES" > openssl
chmod +x openssl

