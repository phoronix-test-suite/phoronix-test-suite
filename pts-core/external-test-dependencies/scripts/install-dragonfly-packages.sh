#!/bin/sh




# Old dropped DragonFly pkgsrc support from older releases
# DragonFlyBSD pkgsrc package installation
exit

echo "For now with this test profile script, please run: phoronix-test-suite install-dependencies xxx as root."

# Check that pkgsrc is setup, below code should work for making sure good state with at least DragonFlyBSD
if [ -d /usr/pkgsrc ] && [ ! -d /usr/pkgsrc/devel ];
then
	cd /usr

	if [ -d /usr/pkgsrc/.git ];
	then
		make pkgsrc-update
	else
		make pkgsrc-create
	fi
fi

if [ -d /usr/pkgsrc ]
then
	for portdir in $*
	do
		if [ -d /usr/pkgsrc/$portdir ];
		then
			cd /usr/pkgsrc/$portdir
			bmake install clean BATCH="yes"
		fi
	done
elif [ -x /usr/local/sbin/pkg ]
then
	for portdir in $*
	do
		# DragonFlyBSD 3.6 now uses dports by default and this method seems to work fine for hitting most packages based upon earlier pkgsrc basename
		pkg install -y `basename $portdir`
	done
fi
