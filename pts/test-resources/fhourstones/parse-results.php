<?php
$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = substr($log_file, 0, strrpos($log_file, "Kpos/sec"));
echo trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, "msec =") + 6));

?>
