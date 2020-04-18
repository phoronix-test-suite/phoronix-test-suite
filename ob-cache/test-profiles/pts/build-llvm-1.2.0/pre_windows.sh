#!/bin/sh

rm -rf build
rm -rf llvm-10.0.0.src
mkdir build
tar -xJf llvm-10.0.0.src.tar.xz

cd build
cmake cmake -G "Unix Makefiles" -DCMAKE_BUILD_TYPE:STRING=Release ../llvm-10.0.0.src

