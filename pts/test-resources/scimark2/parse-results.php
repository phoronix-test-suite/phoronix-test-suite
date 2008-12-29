<?php

$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = trim(substr($log_file, strrpos($log_file, ":") + 1));

	if(($space_pos = strpos($BENCHMARK_RESULTS, " ")) > 0)
		$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, $space_pos));

echo $BENCHMARK_RESULTS;

?>
