#!/bin/sh

cd gcc-11.2.0
make distclean
./configure --disable-multilib --enable-checking=release
