#!/bin/bash
# ensure some data in case of doing a read/rand test
if [ "$1" != "write" ]; then
	microceph.rados bench -p ptstestbench 120 write --no-cleanup --run-name pts
	sleep 3
fi
