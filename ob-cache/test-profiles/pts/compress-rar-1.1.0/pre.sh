#!/bin/sh

rm -rf to-compress
rm -f to-compress.rar
tar -xf linux-4.13.tar.gz
cp -va linux-4.13 to-compress
cp -va linux-4.13 to-compress/copy
rm -rf linux-4.13
