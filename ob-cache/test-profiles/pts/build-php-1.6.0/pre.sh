#!/bin/sh

rm -rf php-8.1.9
tar -xf php-8.1.9.tar.xz
cd php-8.1.9
./configure --without-sqlite3 --without-pdo-sqlite > /dev/null
make clean
