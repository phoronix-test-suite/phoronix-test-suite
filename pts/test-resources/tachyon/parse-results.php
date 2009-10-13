<?php

$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = trim(substr($log_file, strrpos($log_file, "Ray Tracing Time:") + 18));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, " seconds"));
pts_report_numeric_result($BENCHMARK_RESULTS);

?>
