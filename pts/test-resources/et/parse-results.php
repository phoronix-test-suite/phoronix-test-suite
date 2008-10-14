<?php

$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = trim(substr($log_file, 0, strrpos($log_file, " fps")));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, ' ')));
echo $BENCHMARK_RESULTS;

?>
