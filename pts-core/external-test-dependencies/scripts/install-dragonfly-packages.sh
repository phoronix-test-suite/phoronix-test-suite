#!/bin/sh

if [ `whoami` = "root" ]; then
	pkg install -y $*
else
	sudo pkg install -y $*
fi
