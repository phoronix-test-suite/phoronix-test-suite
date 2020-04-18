#!/bin/sh

cd gcc-9.3.0
make distclean
./contrib/download_prerequisites
./configure --disable-multilib --enable-checking=release
