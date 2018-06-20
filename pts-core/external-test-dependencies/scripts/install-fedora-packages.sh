#!/bin/sh

# Fedora / Red Hat package installation
if [ `whoami` = "root" ] && [ ! -w /usr/bin/sudo ]; then
	yum -y install $*
elif [ -x /usr/bin/dnf ]; then
	sudo dnf -y install $*
elif [ `whoami` = "ec2-user" ]; then
	sudo yum -y --skip-broken install $*
else
	echo "Please enter your SUDO password below:" 1>&2 
	read -s -p "Password: " passwd
	if ! echo $passwd | sudo -S -p '' yum -y --skip-broken install $*; then
        	echo "Please enter your ROOT password below:" 1>&2
		su root -c "yum -y --skip-broken install $*"
	fi
fi

exit
