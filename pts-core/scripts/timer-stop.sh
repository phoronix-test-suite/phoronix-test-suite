#!/bin/sh
php -r "\$start_time = @file_get_contents(getenv(\"HOME\") . \"/pts-timer\"); \$end_time = microtime(true); \$time_diff = \$end_time - \$start_time; file_put_contents(getenv(\"HOME\") . \"/pts-timer\", \$time_diff); echo \"\n\nTest-Time: \$time_diff Seconds\n\";"

