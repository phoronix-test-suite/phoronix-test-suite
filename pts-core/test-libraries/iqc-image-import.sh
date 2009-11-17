#!/bin/sh
$PHP_BIN $TEST_LIBRARIES_DIR/iqc-image-import.php $@ > /dev/null 2>&1
echo 111 > /tmp/2

