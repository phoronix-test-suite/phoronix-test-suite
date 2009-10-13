<?php
$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Results: ") + 9);
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "iter") - 1));

if($BENCHMARK_RESULTS < 2)
	$BENCHMARK_RESULTS = 0;

pts_report_numeric_result($BENCHMARK_RESULTS);

?>
