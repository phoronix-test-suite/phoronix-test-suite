#!/bin/sh
rm -rf php-8.3.4
tar -xf php-8.3.4.tar.xz
cd php-8.3.4
./configure --without-sqlite3 --without-pdo-sqlite
make clean
