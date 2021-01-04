#!/bin/sh

tar -xf libpng-1.2.59.tar.gz
mkdir libpng
cd libpng-1.2.59
./configure --prefix=$HOME/libpng
make -j $NUM_CPU_CORES
make install
cd ~

tar -xf IndigoBenchmark_v4.4.15.tar.gz
cp -va libpng/lib/* IndigoBenchmark_v4.4.15

echo "#!/bin/sh
cd IndigoBenchmark_v4.4.15
./indigo_benchmark \$@ > \$LOG_FILE" > indigobench
chmod +x indigobench
