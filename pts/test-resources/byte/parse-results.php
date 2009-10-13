<?php

$file = pts_read_log_file();
$results = trim(substr($file, 0, strrpos($file, "INDEX VALUES")));
$results = trim(substr($results, 0, strrpos($results, "(")));

$result_unit = substr($results, strrpos($results, " ") + 1);

$results = substr($results, 0, (-1 - strlen($result_unit)));
$results = substr($results, strrpos($results, " ") + 1);

pts_report_numeric_result($results);

?>
