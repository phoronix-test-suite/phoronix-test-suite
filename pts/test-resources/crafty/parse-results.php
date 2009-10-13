<?php
$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Total elapsed time: ") + 9);
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, strpos($BENCHMARK_RESULTS, ": ") + 1));

pts_report_numeric_result($BENCHMARK_RESULTS);

?>
