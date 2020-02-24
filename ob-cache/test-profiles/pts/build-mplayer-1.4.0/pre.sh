#!/bin/sh

rm -rf MPlayer-1.4/
tar -xf MPlayer-1.4.tar.xz
cd MPlayer-1.4/
./configure --disable-ivtv > /dev/null
make clean
