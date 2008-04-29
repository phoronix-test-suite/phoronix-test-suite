<?php

$BENCHMARK_RESULTS = substr($argv[1], strpos($argv[1], "Average:"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));
echo trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, ':') + 1));

?>
