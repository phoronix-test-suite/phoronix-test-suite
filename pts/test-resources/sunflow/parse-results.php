<?php

$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = substr($log_file, strpos($log_file, "Average:"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));
preg_match("/([0-9\.:]*)(.{0,2})/", trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, ':') + 1)), $match);

if($match[2] == "ms")
	echo $match[1] / 1000;
else
	echo $match[1];

?>
