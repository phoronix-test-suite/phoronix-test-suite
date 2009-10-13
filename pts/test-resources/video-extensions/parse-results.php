<?php

$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Final Results:") + 14);
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, ".")));
pts_report_numeric_result($BENCHMARK_RESULTS);

?>
