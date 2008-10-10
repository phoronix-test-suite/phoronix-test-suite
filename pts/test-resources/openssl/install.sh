#!/bin/sh

tar -xvf openssl-0.9.8h.tar.gz
cd openssl-0.9.8h/
./config no-zlib
make
cd ..

echo "#!/bin/sh
cd openssl-0.9.8h/
./apps/openssl speed rsa4096 -multi \$NUM_CPU_CORES > \$LOG_FILE 2>&1" > openssl
chmod +x openssl


