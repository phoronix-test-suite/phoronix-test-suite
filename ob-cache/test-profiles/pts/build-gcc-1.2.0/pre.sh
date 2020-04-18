#!/bin/sh

rm -rf gcc-9.3.0
tar -xf gcc-9.3.0.tar.gz

cd gcc-9.3.0
./contrib/download_prerequisites
./configure --disable-multilib --enable-checking=release
make defconfig
make clean
