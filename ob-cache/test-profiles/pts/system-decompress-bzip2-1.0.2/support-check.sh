#!/bin/sh

which bzip2
if [ $? -gt 0 ]; then
	echo "The system must provide bzip2 for this test profile." > $TEST_CUSTOM_ERROR
fi
