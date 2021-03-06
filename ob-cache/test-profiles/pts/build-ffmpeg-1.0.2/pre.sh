#!/bin/sh

rm -rf ffmpeg-4.2.2
tar -xf ffmpeg-4.2.2.tar.bz2
cd ffmpeg-4.2.2
./configure --disable-zlib --disable-doc  > /dev/null
if [ $OS_TYPE = "BSD" ]
then
	gmake clean
else
	make clean
fi
