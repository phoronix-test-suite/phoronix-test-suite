<?php
$log_file = file_get_contents(getenv("LOG_FILE"));

$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Total Time:"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strrpos($BENCHMARK_RESULTS, " seconds"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, strpos($BENCHMARK_RESULTS, "(") + 1);

echo $BENCHMARK_RESULTS;

?>
