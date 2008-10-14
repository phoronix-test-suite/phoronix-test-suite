<?php
$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "average fps =") + 14);
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));
echo trim(substr($BENCHMARK_RESULTS, 0, strrpos($BENCHMARK_RESULTS, ".")));
?>
