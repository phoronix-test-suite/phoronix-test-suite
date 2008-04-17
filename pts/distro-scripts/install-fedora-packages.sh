#!/bin/sh

# Fedora package installation

su root -c "yum -y install $@"
exit
