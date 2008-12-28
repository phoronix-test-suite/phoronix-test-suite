<?php
$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Results: ") + 9);
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "iter") - 1));

if($BENCHMARK_RESULTS < 2)
	$BENCHMARK_RESULTS = 0;

echo $BENCHMARK_RESULTS;

?>
