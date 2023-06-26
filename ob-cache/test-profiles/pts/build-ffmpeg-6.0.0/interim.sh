#!/bin/sh
cd ffmpeg-6.0
if [ $OS_TYPE = "BSD" ]
then
	gmake clean
else
	make clean
fi
