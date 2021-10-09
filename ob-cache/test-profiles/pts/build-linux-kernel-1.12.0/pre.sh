#!/bin/sh

rm -rf linux-5.14
tar -xf linux-5.14.tar.xz

cd linux-5.14
make defconfig
make clean

# Various fixes/workarounds at least for older versions
sed -i 's/CONFIG_STACK_VALIDATION=y/CONFIG_STACK_VALIDATION=n/' .config
sed -i 's/CONFIG_UNWINDER_ORC=y/CONFIG_UNWINDER_ORC=n/' .config
sed -i 's/# CONFIG_UNWINDER_FRAME_POINTER is not set/CONFIG_UNWINDER_FRAME_POINTER=y/' .config

