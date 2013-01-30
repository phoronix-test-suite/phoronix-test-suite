#!/bin/sh

# DragonFlyBSD pkgsrc package installation

echo "For now with this test profile script, please run: phoronix-test-suite install-dependencies xxx as root."

# Check that ports is setup, below code should work for making sure good state with PC-BSD at least
if [ -d /usr/pkgsrc ] && [ ! -d /usr/pkgsrc/devel ];
then
	cd /usr

	if [ -d /usr/pkgsrc/.git ];
	then
		make pkgsrc-update
	elif
		make pkgsrc-create
	fi
fi

for portdir in $*
do
	if [ -d /usr/pkgsrc/$portdir ];
	then
		cd /usr/pkgsrc/$portdir
		bmake install clean BATCH="yes"
	fi
done
