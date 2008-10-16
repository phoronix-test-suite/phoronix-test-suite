#!/bin/sh
$PHP_BIN -r "file_put_contents(getenv(\"HOME\") . \"/pts-timer\", microtime(true));"

