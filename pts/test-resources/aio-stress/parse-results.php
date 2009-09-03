<?php

$log_file = file_get_contents(getenv("LOG_FILE"));

$BENCHMARK_RESULTS = substr($log_file, 0, strpos($log_file, " MB/s)"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, "(") + 1);

echo $BENCHMARK_RESULTS;

?>
