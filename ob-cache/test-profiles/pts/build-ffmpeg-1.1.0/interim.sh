#!/bin/sh
cd ffmpeg-4.4
if [ $OS_TYPE = "BSD" ]
then
	gmake clean
else
	make clean
fi
