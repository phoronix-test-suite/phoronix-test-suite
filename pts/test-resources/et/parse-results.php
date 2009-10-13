<?php

$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = trim(substr($log_file, 0, strrpos($log_file, " fps")));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, ' ')));
pts_report_numeric_result($BENCHMARK_RESULTS);

?>
