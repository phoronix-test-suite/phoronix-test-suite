#!/bin/sh

rm -rf httpd-2.2.17/
tar -zxvf httpd-2.2.17.tar.gz
cd httpd-2.2.17/
./configure > /dev/null
make clean

