#!/bin/sh

rm -rf build
rm -rf llvm-4.0.1.src
mkdir build
tar -xJf llvm-4.0.1.src.tar.xz

cd build
cmake -DCMAKE_BUILD_TYPE:STRING=Release ../llvm-4.0.1.src

