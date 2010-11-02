#!/bin/sh

case $OS_TYPE in
	"MacOSX" )
		diskutil eject /Volumes/Geekbench\ 2.1/
	;;
esac
