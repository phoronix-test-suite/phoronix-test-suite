#!/bin/sh

# Arch package installation

echo "Please enter your root password below:" 1>&2
su root -c "pacman -Sy --noconfirm --needed --asdeps $*"
exit
