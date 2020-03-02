#!/bin/sh

rm -rf linux-3.18-rc6/
tar -xf linux-3.18-rc6.tar.gz

cd linux-3.18-rc6/
make defconfig
make clean
