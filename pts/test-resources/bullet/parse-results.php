<?php

$log_file = pts_read_log_file();
$test_target = getenv("PTS_TEST_ARGUMENTS");

$result = substr($log_file, strrpos($log_file, $test_target . ":") + strlen($test_target) + 2);
$result = trim(substr($result, 0, strpos($result, "\n")));

pts_report_numeric_result($result);

?>
