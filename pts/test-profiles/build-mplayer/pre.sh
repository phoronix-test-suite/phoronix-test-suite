#!/bin/sh

rm -rf MPlayer-1.0rc2/
tar -xjf MPlayer-1.0rc2.tar.bz2
cd MPlayer-1.0rc2/
./configure --disable-ivtv > /dev/null
make clean
