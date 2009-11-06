<?php

$BENCHMARK_RESULTS = substr($argv[1], strrpos($argv[1], "FPS:") + 4);
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n")));
pts_report_numeric_result($BENCHMARK_RESULTS);

?>
