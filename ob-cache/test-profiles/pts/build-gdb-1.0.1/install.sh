#!/bin/sh

tar -xf gdb-9.1.tar.xz

echo "#!/bin/sh

cd gdb-9.1/
mkdir build
cd build
../configure
make -s -j \$NUM_CPU_CORES 2>&1
echo \$? > ~/test-exit-status" > build-gdb

chmod +x build-gdb
