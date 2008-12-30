<?php

$log_file = file_get_contents(getenv("LOG_FILE"));
$arg = trim($log_file);
$arg = substr($arg, 0, strrpos($arg, ' '));
echo substr($arg, strrpos($arg, ' '));

?>
