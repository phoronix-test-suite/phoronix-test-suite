#!/bin/sh
$PHP_BIN $TEST_LIBRARIES_DIR/system-monitoring.php $@ 2>&1 > /dev/null &

