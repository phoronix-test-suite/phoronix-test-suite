<?php
$BENCHMARK_RESULTS = substr($argv[1], strpos($argv[1], "phase 1:") + 8);
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, strpos($BENCHMARK_RESULTS, "fps=") + 4);
$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n")));
echo $BENCHMARK_RESULTS;
?>
