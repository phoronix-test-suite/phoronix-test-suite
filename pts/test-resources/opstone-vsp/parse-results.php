<?php
$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = trim(substr($log_file, strrpos($log_file, "64-bit floating point") + 23));
echo trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, " ")));
?>
