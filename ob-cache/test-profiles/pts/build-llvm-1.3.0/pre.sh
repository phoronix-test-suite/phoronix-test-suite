#!/bin/sh

rm -rf build
rm -rf llvm-llvm-12.0.0.src
mkdir build
tar -xf llvm-12.0.0.src.tar.xz

cd build
if [ $1 = "Ninja" ]
then
	cmake -DCMAKE_BUILD_TYPE=Release -G Ninja ../llvm-12.0.0.src
else
	cmake -DCMAKE_BUILD_TYPE=Release -G "Unix Makefiles" ../llvm-12.0.0.src
fi
