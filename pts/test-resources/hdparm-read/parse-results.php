<?php

$BENCHMARK_RESULTS = trim(substr($argv[1], strpos($argv[1], '=') + 1)); 
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, ' '));
pts_report_numeric_result($BENCHMARK_RESULTS);

?>
