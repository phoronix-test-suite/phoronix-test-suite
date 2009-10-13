<?php

$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, strpos($log_file, "Average:"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));
preg_match("/([0-9\.:]*)(.{0,2})/", trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, ':') + 1)), $match);

if($match[2] == "ms")
	$BENCHMARK_RESULTS = $match[1] / 1000;
else
	$BENCHMARK_RESULTS = $match[1];

pts_report_numeric_result($BENCHMARK_RESULTS);

?>
