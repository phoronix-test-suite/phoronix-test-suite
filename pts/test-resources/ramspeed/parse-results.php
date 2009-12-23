<?php

$log_file = pts_read_log_file();
$arg = trim($log_file);
$arg = substr($arg, 0, strrpos($arg, "MB/s") - 1);
$arg = substr($arg, strrpos($arg, ' '));
pts_report_numeric_result($arg);

?>
