#!/bin/sh
cd ffmpeg-4.2.2
if [ $OS_TYPE = "BSD" ]
then
	gmake clean
else
	make clean
fi
