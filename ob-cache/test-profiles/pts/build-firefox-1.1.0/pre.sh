#!/bin/sh

rm -rf firefox/
rm -rf mozilla-release/

tar -xf firefox-84.0.source.tar.xz
#mv mozilla-release firefox/
mkdir firefox
cd firefox/
../firefox-84.0/configure --enable-release > /dev/null
make clean
