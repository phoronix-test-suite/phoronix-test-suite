<?php

$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, strpos($log_file, "Avr:"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));
$array = explode(" ", $BENCHMARK_RESULTS);
$array2 = array();

foreach($array as $value)
	if(!empty($value))
		array_push($array2, $value);

if(!empty($array2[3]))
	pts_report_numeric_result($array2[3]);
?>
