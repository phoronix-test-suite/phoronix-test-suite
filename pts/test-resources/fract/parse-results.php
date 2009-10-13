<?php

$BENCHMARK_RESULTS = substr($argv[1], strpos($argv[1], "this yields") + 11);
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "FPS")));
pts_report_numeric_result($BENCHMARK_RESULTS);

?>
