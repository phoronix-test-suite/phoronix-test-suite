#!/bin/sh

if [ `whoami` != "root" ]; then
	if [ -x /usr/bin/gksudo ] && [ ! -z "$DISPLAY" ]; then
		ROOT="/usr/bin/gksudo --preserve-env"
	elif [ -x /usr/bin/kdesu ] && [ ! -z "$DISPLAY" ]; then
		ROOT="/usr/bin/kdesu"
	elif [ -x /usr/bin/sudo ]; then
		ROOT="/usr/bin/sudo -E"
	fi
else
	ROOT=""
fi

TMPRUN=`mktemp`

/bin/echo -e "#!/bin/sh\n\n$@" > $TMPRUN
chmod +x $TMPRUN
chmod +x $@
/bin/echo -e "\nThis test requires root access to run.\n" 1>&2
$ROOT $TMPRUN
rm -f $TMPRUN
