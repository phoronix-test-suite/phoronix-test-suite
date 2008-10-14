<?php
$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "seconds =") + 9);
echo trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "fps")));
?>
