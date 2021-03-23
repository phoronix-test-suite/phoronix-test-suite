#!/bin/sh

rm -rf mesa-21.0.0
tar -xf mesa-21.0.0.tar.xz
cd mesa-21.0.0
meson --buildtype=release -Dglx=gallium-xlib -Dvulkan-drivers= -Ddri-drivers= -Dgallium-drivers=swrast -Dplatforms=x11 -Dgallium-omx=disabled -Dllvm=false build
cp -va build build-meson
