<?php
$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = trim(substr($log_file, strrpos($log_file, "FPS:") + 5));
pts_report_numeric_result($BENCHMARK_RESULTS);
?>
