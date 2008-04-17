#!/bin/sh

# Portions based on Debian fglrx install script
# http://phorogit.com/index.php?p=fglrx-packaging.git&b=c67ac96d765ca95130bd07cb240ab69cfc06baa2

if [ `whoami` != "root" ]; then
	if [ -x /usr/bin/gksudo ] && [ ! -z "$DISPLAY" ]; then
		ROOT="/usr/bin/gksudo"
	elif [ -x /usr/bin/kdesu ] && [ ! -z "$DISPLAY" ]; then
		ROOT="/usr/bin/kdesu"
	elif [ -x /usr/bin/sudo ]; then
		ROOT="/usr/bin/sudo"
	fi
else
	ROOT=""
fi

if [ -x /usr/sbin/synaptic ]; then
	SYNAPTIC="TRUE"
else
	SYNAPTIC=""
fi


# if [ ! -z "$SYNAPTIC" ] && [ ! -z "$DISPLAY" ]; then
#	$ROOT "/usr/sbin/synaptic --set-selections --non-interactive $@"
# else
#	$ROOT "apt-get -y install $@"
# fi

$ROOT "apt-get -y install $@"
