#!/bin/sh

tar -xvf zstd-1.3.4.tar.gz
cd zstd-1.3.4/
make
cd ~
cat > compress-zstd <<EOT
#!/bin/sh
./zstd-1.3.4/zstd -19 -T\$NUM_CPU_CORES ubuntu-16.04.3-server-i386.img > /dev/null 2>&1
EOT
chmod +x compress-zstd
