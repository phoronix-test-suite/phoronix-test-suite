<?php

$log_file = pts_read_log_file();
$log_file = substr($log_file, strrpos($log_file, "Overall Geekbench Score:") + 26);
$result = trim(substr($log_file, 0, strpos($log_file, "|")));

pts_report_numeric_result($result);

?>
