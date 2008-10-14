<?php
$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = trim(substr($log_file, strrpos($log_file, "min/avg/max:") + 12));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, strpos($BENCHMARK_RESULTS, ' ')));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, ' ')));
echo $BENCHMARK_RESULTS;
?>
