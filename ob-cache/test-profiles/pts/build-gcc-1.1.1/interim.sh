#!/bin/sh

cd gcc-8.2.0
make distclean
./contrib/download_prerequisites
./configure --disable-multilib --enable-checking=release
