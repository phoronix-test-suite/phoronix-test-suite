#!/bin/bash
rm -rf linux-6.8
tar -xf linux-6.8.tar.xz
cd linux-6.8
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
scripts/config --set-val CONFIG_WERROR n
