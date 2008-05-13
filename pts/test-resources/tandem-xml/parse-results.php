<?php

$BENCHMARK_RESULTS = substr($argv[1], strrpos($argv[1], "tandem_Xml Time:") + 16);
echo trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "Seconds")));
?>
