#!/bin/sh

which tiff2rgba
if [ $? -gt 0 ]; then
	echo "The system must provide tiff2rgba for this test profile. The tiff2rgba command is commonly provided by the libtiff-tools package." > $TEST_CUSTOM_ERROR
fi
