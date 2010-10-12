#!/bin/sh

rm -rf php-5.2.9/
tar -xjf php-5.2.9.tar.bz2
cd php-5.2.9/
./configure --with-libxml-dir=$HOME/libxml2 > /dev/null
make clean
