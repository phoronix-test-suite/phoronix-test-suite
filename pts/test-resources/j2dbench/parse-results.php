<?php

$BENCHMARK_RESULTS = substr($argv[1], strpos($argv[1], "average:") + 9);
echo trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n")));

?>
