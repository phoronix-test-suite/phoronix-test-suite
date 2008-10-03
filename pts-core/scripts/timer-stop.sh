#!/bin/sh
php -r "\$start_time = @file_get_contents(getenv(\"HOME\") . \"/pts-timer\"); \$end_time = microtime(true); file_put_contents(getenv(\"HOME\") . \"/pts-timer\", (\$end_time - \$start_time));"

