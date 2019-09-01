#!/bin/sh

rm -rf build
rm -rf llvm-6.0.1.src
mkdir build
tar -xJf llvm-6.0.1.src.tar.xz

cd build
cmake -DCMAKE_BUILD_TYPE:STRING=Release ../llvm-6.0.1.src

