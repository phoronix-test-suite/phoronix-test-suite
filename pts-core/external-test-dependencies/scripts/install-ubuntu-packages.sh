#!/bin/sh

# The < /dev/null hacks below added for Ubuntu 22.04 LTS...
# Without them, after years of this script working fine, on Ubuntu 22.04 it manages to crash the entire PHP/PTS process...
# Adding < /dev/null to the apt/apt-get lines seems to workaround this odd new issue on Ubuntu 22.04

export DEBIAN_FRONTEND=noninteractive
if [ `whoami` = "root" ] && [ ! -w /usr/bin/sudo ]; then
	apt-get -y --ignore-missing install $* < /dev/null
elif [ `whoami` != "root" ] && [ ! -z "$DISPLAY" ]; then
	if [ -x /usr/bin/gksudo ]; then
		ROOT="/usr/bin/gksudo"
	elif [ -x /usr/bin/kdesudo ]; then
		ROOT="/usr/bin/kdesudo"
	elif [ -x /usr/bin/sudo ]; then
		ROOT="/usr/bin/sudo"
	fi
	$ROOT -- su -c "apt-get -y --ignore-missing install $* < /dev/null"
elif [ -z "$DISPLAY" ]; then
	sudo -- apt-get -y --ignore-missing install $* < /dev/null
else
	su -c "apt-get -y --ignore-missing install $* < /dev/null"
	exit
fi
