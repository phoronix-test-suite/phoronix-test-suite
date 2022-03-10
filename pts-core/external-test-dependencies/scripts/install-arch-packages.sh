#!/bin/sh

# Arch package installation

echo "Checking for root availability, please enter your sudo password below:" 1>&2
if sudo passwd -S root | grep -q 'L' 1>&2; then
    echo "Root account locked, using sudo to install packages" 1>&2
    #echo "Please enter your sudo password below:" 1>&2
    sudo pacman -Sy --noconfirm --needed --asdeps $*
else
    echo "Root account not locked, please enter your root password below:" 1>&2
    su root -c "pacman -Sy --noconfirm --needed --asdeps $*"
fi
exit
