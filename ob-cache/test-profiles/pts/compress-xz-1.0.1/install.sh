#!/bin/sh

tar -xvf xz-5.2.4.tar.bz2
cd xz-5.2.4/
./configure
make -j $NUM_CPU_CORES
cd ~
cat > compress-xz <<EOT
#!/bin/sh
./xz-5.2.4/src/xz/xz -9 -k -T\$NUM_CPU_CORES ubuntu-16.04.3-server-i386.img > /dev/null 2>&1
EOT
chmod +x compress-xz
