#!/bin/sh
php -r "file_put_contents(getenv(\"HOME\") . \"/pts-timer\", microtime(true));"

