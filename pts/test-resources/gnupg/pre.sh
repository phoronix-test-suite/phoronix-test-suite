#!/bin/sh

cd $1
dd if=/dev/zero of=1gbfile bs=1024 count=1048576

