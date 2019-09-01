#!/bin/sh

rm -rf MPlayer-1.0rc3/
tar -xjf MPlayer-1.0rc3.tar.bz2
cd MPlayer-1.0rc3/
./configure --disable-ivtv > /dev/null
make clean
