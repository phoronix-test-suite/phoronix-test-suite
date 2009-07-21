<?php
$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = trim(substr($log_file, strrpos($log_file, "FPS:") + 5));
echo $BENCHMARK_RESULTS;
?>
