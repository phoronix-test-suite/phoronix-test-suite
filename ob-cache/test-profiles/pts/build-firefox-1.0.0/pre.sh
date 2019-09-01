#!/bin/sh

rm -rf firefox/
rm -rf mozilla-release/

tar -xjvf firefox-32.0.source.tar.bz2
#mv mozilla-release firefox/
mkdir firefox
cd firefox/
../mozilla-release/configure --enable-release > /dev/null
make clean