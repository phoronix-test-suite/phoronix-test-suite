#!/bin/sh

# OpenSuSE package installation

echo "Please enter your root password below:" 1>&2
if [ `whoami` = "ec2-user" ] && [ -x /usr/bin/zypper ]; then
	sudo zypper install -l -y -n --force-resolution $*
elif [ -x /usr/bin/zypper ]; then
	su root -c "zypper install -l -y -n --force-resolution $*"
else
	su root -c "yast -i $*"
fi

exit
