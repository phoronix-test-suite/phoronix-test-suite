#!/bin/sh

rm -rf mysql-5.1.30/
tar -zxvf mysql-5.1.30.tar.gz
cd mysql-5.1.30/
./configure > /dev/null 2>&1
make clean
