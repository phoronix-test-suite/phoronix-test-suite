#!/bin/sh

# Fedora / Red Hat package installation
if [ -x /usr/bin/dnf ]; then
	sudo dnf -y install $*
elif [ `whoami` = "ec2-user" ]; then
	sudo yum -y --skip-broken install $*
else
	echo "Please enter your root password below:" 1>&2
	su root -c "yum -y --skip-broken install $*"
fi

exit
