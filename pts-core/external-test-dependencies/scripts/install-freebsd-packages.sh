#!/bin/sh

# FreeBSD package installation

echo "For now with this test profile script, please run: phoronix-test-suite install-dependencies xxx as root."
# sudo seems a bit odd at times on PC-BSD 9.1

# Check that ports is setup, below code should work for making sure good state with PC-BSD at least
if [ -d /usr/ports/distfiles ] && [ ! -d /usr/ports/devel ] && [ -x /usr/sbin/portsnap ];
then
	portsnap fetch extract
fi

for portdir in $*
do
	if [ -d /usr/ports/$portdir ];
	then
		cd /usr/ports/$portdir
		make config-recursive install clean BATCH="yes"
	fi
done

#echo "Please enter your root password below:" 1>&2
#su root -c "PACKAGESITE=\"ftp://ftp.freebsd.org/pub/FreeBSD/ports/i386/packages-7-stable/Latest/\" pkg_add -r $*"
#exit
