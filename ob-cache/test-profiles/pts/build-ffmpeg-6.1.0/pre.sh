#!/bin/sh
rm -rf ffmpeg-6.1
tar -xf ffmpeg-6.1.tar.xz
cd ffmpeg-6.1
./configure --disable-zlib --disable-doc  > /dev/null
if [ $OS_TYPE = "BSD" ]
then
	gmake clean
else
	make clean
fi
