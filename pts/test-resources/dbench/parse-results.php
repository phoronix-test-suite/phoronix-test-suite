<?php

$log_file = file_get_contents(getenv("LOG_FILE"));

$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Throughput") + 11);
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, " MB/sec"));

echo $BENCHMARK_RESULTS;

?>
