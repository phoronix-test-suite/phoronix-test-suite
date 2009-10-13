<?php
$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, 0, strrpos($log_file, "Kpos/sec"));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, "msec =") + 6));
pts_report_numeric_result($BENCHMARK_RESULTS);

?>
