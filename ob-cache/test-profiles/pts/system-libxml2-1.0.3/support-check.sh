#!/bin/sh

which xmllint
if [ $? -gt 0 ]; then
	echo "The xmllint program is required for this test, which is commonly provided by libxml2." > $TEST_CUSTOM_ERROR
fi
