<?php

$log_file = pts_read_log_file();
list($BENCHMARK_RESULTS) = split(" ",$log_file);
pts_report_numeric_result($BENCHMARK_RESULTS);

?>
