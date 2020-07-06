#!/bin/sh

rm -rf linux-5.4.35
tar -xf linux-5.4.35.tar.xz

cd linux-5.4.35
make defconfig
make clean

sed -i 's/CONFIG_STACK_VALIDATION=y/CONFIG_STACK_VALIDATION=n/' .config
sed -i 's/CONFIG_UNWINDER_ORC=y/CONFIG_UNWINDER_ORC=n/' .config
sed -i 's/# CONFIG_UNWINDER_FRAME_POINTER is not set/CONFIG_UNWINDER_FRAME_POINTER=y/' .config

