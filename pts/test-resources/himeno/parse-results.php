<?php

$log_file = pts_read_log_file();
$arg = substr($log_file, strrpos($log_file, 'MFLOPS measured : ') + 18);
$arg = substr($arg, 0, strpos($arg, '	'));
pts_report_numeric_result($arg);

?>
