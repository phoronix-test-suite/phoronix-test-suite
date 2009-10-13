<?php

$log_file = pts_read_log_file();

$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "GMPbench result: ") + 17);
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));

pts_report_numeric_result($BENCHMARK_RESULTS);

?>
