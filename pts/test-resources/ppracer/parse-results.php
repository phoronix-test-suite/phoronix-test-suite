<?php

$log_file = trim(file_get_contents(getenv("LOG_FILE")));
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Average FPS:") + 13);
echo trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n")));

?>
