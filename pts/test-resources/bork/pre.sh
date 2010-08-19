#!/bin/sh

rm -f encryptfile.bork
dd if=/dev/zero of=encryptfile bs=2048 count=1048576

