<?php
$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, strpos($log_file, "(") + 1);
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, " per second"));
pts_report_numeric_result($BENCHMARK_RESULTS);
?>
