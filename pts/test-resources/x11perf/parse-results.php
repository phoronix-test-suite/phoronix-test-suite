<?php

$BENCHMARK_RESULTS = substr($argv[1], 0, strrpos($argv[1], "/sec"));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, " ")));

if(substr($BENCHMARK_RESULTS, 0, 1) == "(")
	$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 1);

echo $BENCHMARK_RESULTS;
?>
