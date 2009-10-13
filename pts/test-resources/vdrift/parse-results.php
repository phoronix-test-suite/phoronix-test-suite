<?php

$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Average frame-rate:") + 20);
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, " f"));
pts_report_numeric_result($BENCHMARK_RESULTS);

?>
