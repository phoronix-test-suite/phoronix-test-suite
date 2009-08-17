#!/bin/sh
$PHP_BIN -r "is_file(getenv(\"HOME\") . \"/pts-timer\") && unlink(getenv(\"HOME\") . \"/pts-timer\"); file_put_contents(getenv(\"HOME\") . \"/pts-timer-start\", microtime(true));"

