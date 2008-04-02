<?php

$BENCHMARK_RESULTS = trim(substr($argv[1], strrpos($argv[1], "min/avg/max:") + 12));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, strpos($BENCHMARK_RESULTS, ' ')));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, ' ')));
echo $BENCHMARK_RESULTS;
?>
