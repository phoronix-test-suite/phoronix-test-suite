#!/bin/sh

# Mandriva package installation

echo "Please enter your root password below:" 1>&2
su - root -c "urpmi --auto $*"
exit
