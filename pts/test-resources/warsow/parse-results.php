<?php

$BENCHMARK_RESULTS = trim(substr($argv[1], strrpos($argv[1], "seconds:") + 9));
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "fps")));
echo $BENCHMARK_RESULTS;
?>
