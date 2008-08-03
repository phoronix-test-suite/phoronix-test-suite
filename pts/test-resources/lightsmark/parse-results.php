<?php

$BENCHMARK_RESULTS = substr($argv[1], strrpos($argv[1], "average fps =") + 14);
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));
echo trim(substr($BENCHMARK_RESULTS, 0, strrpos($BENCHMARK_RESULTS, ".")));
?>
