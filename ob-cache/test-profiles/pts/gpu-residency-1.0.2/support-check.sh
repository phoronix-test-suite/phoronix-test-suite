#!/bin/sh

cat /sys/class/drm/card0/power/rc6_residency_ms
if [ $? -gt 0 ]; then
	echo "Intel graphics hardware running on a modern Linux kernel is required for this test profile." > $TEST_CUSTOM_ERROR
fi
