#!/bin/sh

if [ -f /.dockerenv ]; then
	su -c "apt-get update $*"
fi

# Debian, unlike Ubuntu, doesn't sudo it users by default
if [ -x /usr/bin/aptitude ]; then
	# aptitude is nice since it doesn't fail if a non-existant package is hit
	# See: http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=503215
	su -c "aptitude -y install $*"
elif [ `whoami` = "admin" ] || [ -f /dev/.cros_milestone ] && [ -x /usr/bin/sudo ]; then
	# Amazon EC2 is admin user, sudo works
	# crostini (Chrome OS) also defaults to sudo https://chromeos.dev/en/linux/setup#installing-linux-apps-and-packages
	sudo apt-get -y --ignore-missing install $*
else
	su -c "apt-get -y --ignore-missing install $*"
fi
