#!/bin/sh

which gzip
if [ $? -gt 0 ]; then
	echo "The system must provide gzip for this test profile." > $TEST_CUSTOM_ERROR
fi
