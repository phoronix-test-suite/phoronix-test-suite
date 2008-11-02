#!/bin/sh
(sleep $2; killall -9 $1) > /dev/null 2>&1 &

