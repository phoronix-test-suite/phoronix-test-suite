#!/bin/sh

tar -xvf iozone3_291.tar
cd iozone3_291/src/current/

case $OS_ARCH in
	"x86_64" )
	make linux-AMD64
	;;
	* )
	make linux
	;;
esac
mv iozone ../../../iozone
