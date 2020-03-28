#!/bin/sh

# The below script is a simple, distribution-agnostic install script for PTS assuming you're running as root
# Feel free to modify to make use of your own RPMs, Debian packages, or other install preferences.

# If you wish to adapt this reference script, delete the exit call below
exit

# The Git revision you wish to sync the systems to, or empty for riding latest git
GIT_COMMIT_TO_USE="df9be063f20db32866f7bded158be3fc649e04d8"
FRESH_CLONE=0

if [ ! -d /phoronix-test-suite ]
then
	cd /
	git clone https://github.com/phoronix-test-suite/phoronix-test-suite.git
	FRESH_CLONE=1
fi

cd /phoronix-test-suite
CURRENT_GIT_COMMIT=`git rev-parse HEAD`

if [ "$GIT_COMMIT_TO_USE" != "$CURRENT_GIT_COMMIT" ] || [ "$FRESH_CLONE" == "1" ]
then
	git checkout master
	git pull
	git checkout $GIT_COMMIT_TO_USE
	./install-sh
	reboot
fi
