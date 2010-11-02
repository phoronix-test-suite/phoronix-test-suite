#!/bin/sh

rm -rf httpd-2.2.11/
tar -zxvf httpd-2.2.11.tar.gz
cd httpd-2.2.11/
./configure > /dev/null
make clean

