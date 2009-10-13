<?php
$log_file = pts_read_log_file();
$results = trim(substr($log_file, 0, strpos($log_file, "Ops/s")));
pts_report_numeric_result($results);
?>
