#!/bin/sh

which djpeg
if [ $? -gt 0 ]; then
	echo "The system must provide djpeg for this test profile to decompress JPEG images. The libjpeg-progs package commonly provides djpeg." > $TEST_CUSTOM_ERROR
fi
