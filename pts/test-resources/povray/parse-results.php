<?php
$log_file = pts_read_log_file();

$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Total Time:"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strrpos($BENCHMARK_RESULTS, " seconds"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, strpos($BENCHMARK_RESULTS, "(") + 1);

pts_report_numeric_result($BENCHMARK_RESULTS);

?>
