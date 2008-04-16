#!/bin/sh

# Fedora package installation

if [ `whoami` != "root" ]; then
	ROOT="/usr/bin/sudo"
else
	ROOT=""
fi

$ROOT "yum -y install $@"
