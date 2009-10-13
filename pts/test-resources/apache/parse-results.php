<?php

$file = pts_read_log_file();
$results = trim(substr($file, strrpos($file, "Requests per second:") + 22));
$results = trim(substr($results, 0, strpos($results, " ")));

pts_report_numeric_result($results);

?>
