<?php

$log_file = file_get_contents(getenv("LOG_FILE"));

$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Rendering took: ") + 16);
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, " seconds"));

echo $BENCHMARK_RESULTS;

?>
