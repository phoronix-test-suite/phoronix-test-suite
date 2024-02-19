#!/bin/sh
cd gcc-13.2.0
make distclean
./configure --disable-multilib --enable-checking=release
