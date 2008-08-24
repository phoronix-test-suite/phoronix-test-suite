<?php

$BENCHMARK_RESULTS = substr($argv[1], 0, strrpos($argv[1], "Kpos/sec"));
echo trim(substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, "msec =") + 6));

?>
