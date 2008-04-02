<?php

$BENCHMARK_RESULTS = substr($argv[1], strrpos($argv[1], "PHP Build Time:") + 15);
echo trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "Seconds")));
?>
