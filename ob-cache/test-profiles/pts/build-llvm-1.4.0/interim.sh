#!/bin/sh

rm -rf build
rm -rf llvm-13.0.0.src
mkdir build
tar -xf llvm-13.0.0.src.tar.xz

cd build
if [ "$1" = "Ninja" ]
then
	cmake -DCMAKE_BUILD_TYPE=Release -G Ninja ../llvm-13.0.0.src
else
	cmake -DCMAKE_BUILD_TYPE=Release -G "Unix Makefiles" ../llvm-13.0.0.src
fi
