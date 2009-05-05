<?php
$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Total elapsed time: ") + 9);
echo trim(substr($BENCHMARK_RESULTS, strpos($BENCHMARK_RESULTS, ": ") + 1));
?>
