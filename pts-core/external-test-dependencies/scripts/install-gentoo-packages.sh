#!/bin/sh
 
# Gentoo package installation

if [ `whoami` = "ec2-user" ]; then
	sudo emerge -v $*
else

	echo "Please enter your root password below:" 1>&2
	su root -c "emerge -v $*"
	exit
fi
