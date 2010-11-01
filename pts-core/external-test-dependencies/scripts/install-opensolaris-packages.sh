#!/bin/sh

# OpenSolaris IPS Installer

echo "Please enter your root password below:" 1>&2
su root -c "pkg install --accept $*"
