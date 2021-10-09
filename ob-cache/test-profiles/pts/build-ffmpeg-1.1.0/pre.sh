#!/bin/sh

rm -rf ffmpeg-4.4
tar -xf ffmpeg-4.4.tar.bz2
cd ffmpeg-4.4
./configure --disable-zlib --disable-doc  > /dev/null
if [ $OS_TYPE = "BSD" ]
then
	gmake clean
else
	make clean
fi
