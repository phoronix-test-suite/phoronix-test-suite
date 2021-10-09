#!/bin/sh

tar -xf gdb-10.2.tar.xz

echo "#!/bin/sh

cd gdb-10.2/
mkdir build
cd build
../configure
make -s -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > build-gdb

chmod +x build-gdb
