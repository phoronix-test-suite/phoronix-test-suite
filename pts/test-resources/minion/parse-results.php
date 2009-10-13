<?php

$file = pts_read_log_file();
$results = substr($file, strrpos($file, "Total Wall Time: ") + 17);
$results = substr($results, 0, strpos($results, "\n"));
pts_report_numeric_result($results);

?>
