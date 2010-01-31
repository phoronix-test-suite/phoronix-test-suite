#!/bin/sh
if [ `whoami` != "root" ]; then
	if [ -x /usr/bin/gksudo ] && [ ! -z "$DISPLAY" ]; then
		ROOT="/usr/bin/gksudo"
	elif [ -x /usr/bin/kdesudo ] && [ ! -z "$DISPLAY" ]; then
		ROOT="/usr/bin/kdesudo"
	elif [ -x /usr/bin/sudo ]; then
		ROOT="/usr/bin/sudo"
	fi
else
	ROOT="su -c"
fi

if [ -x /usr/bin/aptitude ]; then
	# aptitude is nice since it doesn't fail if a non-existant package is hit
	# See: http://bugs.debian.org/cgi-bin/bugreport.cgi?bug=503215
	$ROOT "aptitude -y --allow-untrusted install $*"
else
	$ROOT "apt-get -y --ignore-missing install $*"
fi
