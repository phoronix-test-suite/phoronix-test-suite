#!/bin/sh

# Mandriva package installation

echo "Please enter your root password below:" 1>&2

if which dnf >/dev/null 2>&1 ;
then
    su - root -c "dnf -y install $*"
else
	su - root -c "urpmi --auto $*"
fi
exit
