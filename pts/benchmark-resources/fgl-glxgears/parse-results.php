<?php

$BENCHMARK_RESULTS = trim(substr($argv[1], strpos($argv[1], '=') + 1));
echo substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, ' '));

?>
