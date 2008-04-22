#!/bin/sh

# CentOS package installation

su root -c "yum -y install $@"
exit
