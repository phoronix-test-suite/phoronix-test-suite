#!/bin/sh

cd $1

if [ ! -f iozone3_291.tar ]
	then
     wget http://www.iozone.org/src/current/iozone3_291.tar -O iozone3_291.tar
fi

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
