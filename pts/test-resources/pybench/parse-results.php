<?php

$file = pts_read_log_file();
$results = trim(substr($file, strrpos($file, "Totals:") + 8));
$results = substr($results, 0, strpos($results, "ms"));

pts_report_numeric_result($results);

?>
