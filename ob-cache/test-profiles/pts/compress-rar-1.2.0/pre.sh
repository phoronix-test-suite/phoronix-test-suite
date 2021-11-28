#!/bin/sh

rm -rf to-compress
rm -f to-compress.rar
tar -xf linux-5.14.tar.xz
cp -va linux-5.14 to-compress
cp -va linux-5.14 to-compress/copy
rm -rf linux-5.14
