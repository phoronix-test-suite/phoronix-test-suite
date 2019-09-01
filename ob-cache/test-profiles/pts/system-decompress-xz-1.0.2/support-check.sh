#!/bin/sh

which xz
if [ $? -gt 0 ]; then
	echo "The system must provide xz for this test profile. Some Linux distributions provided xz through the xz-utils package." > $TEST_CUSTOM_ERROR
fi
