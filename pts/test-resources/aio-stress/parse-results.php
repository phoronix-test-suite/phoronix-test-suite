<?php

$log_file = pts_read_log_file();

$BENCHMARK_RESULTS = substr($log_file, 0, strpos($log_file, " MB/s)"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, "(") + 1);

pts_report_numeric_result($BENCHMARK_RESULTS);

?>
