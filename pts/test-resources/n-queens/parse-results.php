<?php

$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "elapsed time"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, strpos($BENCHMARK_RESULTS, ":") + 1);
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n")));
pts_report_numeric_result($BENCHMARK_RESULTS);

?>
