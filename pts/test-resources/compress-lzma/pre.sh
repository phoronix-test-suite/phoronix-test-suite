#!/bin/sh

cd $1
dd if=/dev/urandom of=compressfile bs=1024 count=262144

