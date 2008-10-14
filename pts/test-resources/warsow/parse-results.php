<?php
$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = trim(substr($log_file, strrpos($log_file, "seconds:") + 9));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "fps")));
echo $BENCHMARK_RESULTS;
?>
