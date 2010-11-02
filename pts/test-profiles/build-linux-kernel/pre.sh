#!/bin/sh

rm -rf linux-2.6.25/
tar -xjf linux-2.6.25.tar.bz2

case $OS_ARCH in
	"x86_64" )
	cp -f linux-2625-config-x86_64 linux-2.6.25/.config
	;;
	* )
	cp -f linux-2625-config-x86 linux-2.6.25/.config
	;;
esac

cd linux-2.6.25/
make clean
