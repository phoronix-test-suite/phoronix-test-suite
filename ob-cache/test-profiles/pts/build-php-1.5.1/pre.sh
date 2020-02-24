#!/bin/sh

rm -rf php-7.4.2
tar -xf php-7.4.2.tar.bz2
cd php-7.4.2/
./configure --without-sqlite3 --without-pdo-sqlite > /dev/null
make clean
