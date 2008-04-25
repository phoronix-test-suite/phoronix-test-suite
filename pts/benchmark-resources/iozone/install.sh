#!/bin/sh

cd $1

tar -xvf iozone3_291.tar
cd iozone3_291/src/current/

case `uname -m` in
	"x86_64" )
	make linux-AMD64
	;;
	* )
	make linux
	;;
esac
mv iozone ../../../iozone
