<?php

preg_match("/\((.{0,1})Flops\)/", $argv[1], $match);

switch($match[1])
{
	case 'K':
		$factor = 0.000001;
		break;
	case 'M':
		$factor = 0.001;
		break;
	case 'G':
		$factor = 1;
		break;
	case 'T':
		$factor = 1000;
		break;
	case 'P':
		$factor = 1000000;
		break;
	default:
		$factor = 0.000000001; //nothing detected, therefore flops
		break;
}

$BENCHMARK_RESULTS = substr($argv[1], strpos($argv[1], "Performance:"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));
$array = explode(" ", $BENCHMARK_RESULTS);
$array2 = array();

foreach($array as $value)
	if(!empty($value))
		array_push($array2, $value);

if(!empty($array2[2]))
	echo $array2[2] * $factor;
?>
