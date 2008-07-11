<?php

$BENCHMARK_RESULTS = substr($argv[1], strrpos($argv[1], "Final Results:") + 14);
echo trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, ".")));
?>
