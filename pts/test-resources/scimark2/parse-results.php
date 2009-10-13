<?php

$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = trim(substr($log_file, strrpos($log_file, ":") + 1));

	if(($space_pos = strpos($BENCHMARK_RESULTS, " ")) > 0)
		$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, $space_pos));

pts_report_numeric_result($BENCHMARK_RESULTS);

?>
