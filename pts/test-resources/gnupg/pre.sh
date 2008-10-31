#!/bin/sh

cd $1
dd if=/dev/zero of=2gbfile bs=2048 count=1048576

