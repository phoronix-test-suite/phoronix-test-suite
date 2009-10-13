<?php

$log_file = pts_read_log_file();
//$test_target = getenv("PTS_TEST_ARGUMENTS");

$result = substr($log_file, strrpos($log_file, "Mop/s total     =") + 19);
$result = trim(substr($result, 0, strpos($result, "\n")));
pts_report_numeric_result($result);

?>
