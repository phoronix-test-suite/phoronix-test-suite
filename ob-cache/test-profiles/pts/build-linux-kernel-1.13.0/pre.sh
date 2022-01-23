#!/bin/bash

rm -rf linux-5.16
tar -xf linux-5.16.tar.xz

cd linux-5.16

if [ -z "$@" ]
then
	# This is for old PTS clients not passing anything per older old test profile configs that may be in suite...
	export LINUX_MAKE_CONFIG="defconfig"
else
	export LINUX_MAKE_CONFIG="$1"
fi

echo "make $LINUX_MAKE_CONFIG"
make "$LINUX_MAKE_CONFIG"
make clean

