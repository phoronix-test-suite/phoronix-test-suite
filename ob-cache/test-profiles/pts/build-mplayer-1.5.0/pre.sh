#!/bin/sh

rm -rf MPlayer-1.5/
tar -xf MPlayer-1.5.tar.xz
cd MPlayer-1.5/
./configure > /dev/null
make clean
