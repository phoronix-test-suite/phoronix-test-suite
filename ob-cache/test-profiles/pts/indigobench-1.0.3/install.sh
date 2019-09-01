#!/bin/sh

tar -xf libpng-1.2.59.tar.gz
mkdir libpng
cd libpng-1.2.59
./configure --prefix=$HOME/libpng
make -j $NUM_CPU_CORES
make install
cd ~

tar -xf IndigoBenchmark_x64_v4.0.64.tar.gz
cp -va libpng/lib/* IndigoBenchmark_x64_v4.0.64

echo "#!/bin/sh
cd IndigoBenchmark_x64_v4.0.64
./indigo_benchmark \$@ > \$LOG_FILE" > indigobench
chmod +x indigobench

