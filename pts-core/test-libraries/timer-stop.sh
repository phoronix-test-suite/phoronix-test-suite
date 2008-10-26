#!/bin/sh
$PHP_BIN -r "\$start_time = @file_get_contents(getenv(\"HOME\") . \"/pts-timer\"); \$end_time = microtime(true); \$time_diff = \$end_time - \$start_time; if(\$time_diff < 3) { \$time_diff = 0; } file_put_contents(getenv(\"HOME\") . \"/pts-timer\", \$time_diff); echo \"\n\nTest-Time: \$time_diff Seconds\n\";"

