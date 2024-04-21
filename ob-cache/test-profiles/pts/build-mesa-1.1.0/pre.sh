#!/bin/sh
rm -rf mesa-24.0.3
tar -xf mesa-24.0.3.tar.xz
cd mesa-24.0.3
meson --buildtype=release -Dglx=xlib -Dvulkan-drivers= -Dgallium-drivers=swrast -Dplatforms=x11 -Dgallium-omx=disabled -Dllvm=disabled build
cp -va build build-meson
