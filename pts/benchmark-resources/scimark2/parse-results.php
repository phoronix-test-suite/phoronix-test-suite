<?php

$BENCHMARK_RESULTS = substr($argv[1], strpos($argv[1], "Composite Score:") + 16);
echo trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n")));

?>
