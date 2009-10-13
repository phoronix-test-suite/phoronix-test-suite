<?php
$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, strpos($log_file, "phase 1:") + 8);
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, strpos($BENCHMARK_RESULTS, "fps=") + 4);
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n")));

if(intval($BENCHMARK_RESULTS) == 78)
	$BENCHMARK_RESULTS = "0.00";

pts_report_numeric_result($BENCHMARK_RESULTS);
?>
