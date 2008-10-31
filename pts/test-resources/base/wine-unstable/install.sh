#!/bin/sh

THIS_DIR=$(pwd)
mkdir $THIS_DIR/wine_env/

tar -jxvf wine-1.1.7.tar.bz2
cd wine-1.1.7/
./configure --prefix=$THIS_DIR/wine_env/
make -j $NUM_CPU_JOBS depend
make -j $NUM_CPU_JOBS
make install

cd ..
rm -rf wine-1.1.7/
ln -s wine_env/bin/wine wine
