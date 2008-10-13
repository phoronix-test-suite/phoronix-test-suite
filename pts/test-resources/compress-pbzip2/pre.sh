#!/bin/sh

cd $1
dd if=/dev/urandom of=compressfile bs=2048 count=524288

