#!/bin/sh

tar -xf libframetime-20150110.tar.gz
cd libframetime
make
cp libframetime.so ~
