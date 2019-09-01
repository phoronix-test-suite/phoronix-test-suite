#!/bin/sh

rm -rf php-7.1.9/
tar -xjf php-7.1.9.tar.bz2
cd php-7.1.9/
./configure --with-libxml-dir=$HOME/libxml2 > /dev/null
make clean
