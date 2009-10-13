<?php
$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "average fps =") + 14);
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));
$BENCHMARK_RESULTS =  trim(substr($BENCHMARK_RESULTS, 0, strrpos($BENCHMARK_RESULTS, ".")));
pts_report_numeric_result($BENCHMARK_RESULTS);
?>
