<?php

$log_file = file_get_contents(getenv("LOG_FILE"));
$log_file = substr($log_file, strrpos($log_file, "Overall Geekbench Score:") + 26);
$result = trim(substr($log_file, 0, strpos($log_file, "|")));

echo $result;

?>
