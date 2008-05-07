<?php

$BENCHMARK_RESULTS = substr($argv[1], strpos($argv[1], "Performance:"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));
$array = explode(" ", $BENCHMARK_RESULTS);
$array2 = array();

foreach($array as $value)
	if(!empty($value))
		array_push($array2, $value);

if(!empty($array2[2]))
	echo $array2[2];
?>
