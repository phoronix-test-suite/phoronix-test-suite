#!/bin/sh

# The below script is a simple, distribution-agnostic install script for PTS assuming you're running as root
# Feel free to modify to make use of your own RPMs, Debian packages, or other install preferences.

# If you wish to adapt this reference script, delete the exit call below
exit

# The Git revision you wish to sync the systems to, or empty for riding latest git
GIT_COMMIT_TO_USE="ceabd81039c33579a1ba1802038263572de935b6"

if [ ! -w /root ]
then
	exit
fi

if [ ! -d /root/phoronix-test-suite ]
then
	cd /root
	git clone https://github.com/phoronix-test-suite/phoronix-test-suite.git
fi

cd /root/phoronix-test-suite
CURRENT_GIT_COMMIT=`git rev-parse HEAD`

if [ "$GIT_COMMIT_TO_USE" != "$CURRENT_GIT_COMMIT" ]
then
	git pull
	git checkout $GIT_COMMIT_TO_USE
	./install-sh
	reboot
fi
