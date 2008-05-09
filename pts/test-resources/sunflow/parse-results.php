<?php

$BENCHMARK_RESULTS = substr($argv[1], strpos($argv[1], "Average:"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));
preg_match("/([0-9\.:]*)(.{0,2})/", trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, ':') + 1)), $match);

if($match[2] == "ms")
	echo $match[1] / 1000;
else
	echo $match[1];

?>
