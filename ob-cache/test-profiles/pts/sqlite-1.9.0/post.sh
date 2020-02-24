#!/bin/sh

if [ "X$@" = "X" ]
then
	TEST_PATH=`pwd`
else
	TEST_PATH=$@
fi

rm -f $TEST_PATH/benchmark.db
