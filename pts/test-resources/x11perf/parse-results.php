<?php
$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, 0, strrpos($log_file, "/sec"));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, " ")));

if(substr($BENCHMARK_RESULTS, 0, 1) == "(")
	$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 1);

pts_report_numeric_result($BENCHMARK_RESULTS);
?>
