<?php

$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Average frame-rate:") + 20);
echo substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, " f"));

?>
