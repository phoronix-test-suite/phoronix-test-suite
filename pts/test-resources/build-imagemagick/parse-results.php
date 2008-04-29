<?php

$BENCHMARK_RESULTS = substr($argv[1], strrpos($argv[1], "ImageMagick Build Time:") + 23);
echo trim(substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "Seconds")));
?>
