<?php

$BENCHMARK_RESULTS = trim(substr($argv[1], strrpos($argv[1], ":") + 1));

	if(($space_pos = strpos($BENCHMARK_RESULTS, " ")) > 0)
		$BENCHMARK_RESULTS = trim(substr($BENCHMARK_RESULTS, 0, $space_pos));

echo $BENCHMARK_RESULTS;

?>
