<?php
$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = substr($log_file, 0, strrpos($log_file, "/sec"));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, " ")));

if(substr($BENCHMARK_RESULTS, 0, 1) == "(")
	$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 1);

echo $BENCHMARK_RESULTS;
?>
