#!/bin/sh
rm -rf Python-3.10.6
tar -xf Python-3.10.6.tgz
cd Python-3.10.6
./configure $@
make clean
