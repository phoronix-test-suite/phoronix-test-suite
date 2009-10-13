<?php

$log_file = pts_read_log_file();
$test_target = getenv("PTS_TEST_ARGUMENTS");

$result = trim(substr($log_file, strrpos($log_file, $test_target . ":") + strlen($test_target) + 1));
$result = substr($result, 0, strpos($result, " "));

pts_report_numeric_result($result);

?>
