#!/bin/sh

rm -rf gcc-11.2.0
tar -xf gcc-11.2.0.tar.xz

cd gcc-11.2.0
./configure --disable-multilib --enable-checking=release
make clean
