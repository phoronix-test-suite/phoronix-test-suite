<?php
$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = substr($log_file, strpos($log_file, "(") + 1);
echo substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, " per second"));
?>
