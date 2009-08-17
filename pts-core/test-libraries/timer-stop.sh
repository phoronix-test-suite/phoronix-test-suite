#!/bin/sh
$PHP_BIN -r "\$start_time = @file_get_contents(getenv(\"HOME\") . \"/pts-timer-start\"); \$end_time = microtime(true); unlink(getenv(\"HOME\") . \"/pts-timer-start\"); \$time_diff = \$end_time - \$start_time; if(\$time_diff < 3 || \$time_diff > 12000000) { \$time_diff = 0; } file_put_contents(getenv(\"HOME\") . \"/pts-timer\", \$time_diff); echo \"\n\nTest-Time: \$time_diff Seconds\n\";"

