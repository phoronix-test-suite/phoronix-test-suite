#!/bin/sh

which systemd-analyze
if [ $? -gt 0 ]; then
	echo "Systemd is required for this test." > $TEST_CUSTOM_ERROR
fi
