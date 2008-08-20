<?php

$BENCHMARK_RESULTS = trim(substr($argv[1], 0, strrpos($argv[1], " fps")));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, ' ')));
echo $BENCHMARK_RESULTS;
?>
