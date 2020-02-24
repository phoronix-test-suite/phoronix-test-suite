#!/bin/sh

rm -rf linux-4.13/
tar -xf linux-4.13.tar.gz

cd linux-4.13/
make defconfig
make clean
