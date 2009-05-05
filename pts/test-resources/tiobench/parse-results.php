<?php

$log_file = file_get_contents(getenv("LOG_FILE"));
$log_file = substr($log_file, 0, strrpos($log_file, "Total") - 3);
$log_file = substr($log_file, 0, strrpos($log_file, "\n") - 2);
$log_file = substr($log_file, strrpos($log_file, "\n"));
$log_parts = explode("|", $log_file);

$ms = trim(substr($log_parts[2], 0, strpos($log_parts[2], " ms")));

echo $ms * 1000; // convert milliseconds to microseconds

?>
