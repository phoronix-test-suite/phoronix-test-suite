<?php

$log_file = pts_read_log_file();
$log_file = substr($log_file, 0, strrpos($log_file, "MB/s"));
$result = trim(substr($log_file, strrpos($log_file, "|") + 1));

pts_report_numeric_result($result);

?>
