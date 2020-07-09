#!/bin/sh
export DEBIAN_FRONTEND=noninteractive
if [ `whoami` = "root" ] && [ ! -w /usr/bin/sudo ]; then
	apt-get -y --ignore-missing install $*
elif [ `whoami` != "root" ] && [ ! -z "$DISPLAY" ]; then
	if [ -x /usr/bin/gksudo ]; then
		ROOT="/usr/bin/gksudo"
	elif [ -x /usr/bin/kdesudo ]; then
		ROOT="/usr/bin/kdesudo"
	elif [ -x /usr/bin/sudo ]; then
		ROOT="/usr/bin/sudo"
	fi
	$ROOT -- su -c "apt-get -y --ignore-missing install $*"
elif [ -z "$DISPLAY" ]; then
	sudo -- apt-get -y --ignore-missing install $*
else
	su -c "apt-get -y --ignore-missing install $*"
	exit
fi

# if [ -x /usr/bin/aptitude ]; then
	# aptitude is nice since it doesn't fail if a non-existant package is hit
	# See: http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=503215
#	$ROOT -- "aptitude -y install $*"
#fi
