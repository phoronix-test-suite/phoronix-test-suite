<?php

$results = trim($argv[1]);
$BENCHMARK_RESULTS = trim(substr($results, strrpos($results, "\n") + 1));

$test_parts = explode(",", $BENCHMARK_RESULTS);
$test_target = trim(@file_get_contents("TEST_TYPE"));

$BENCHMARK_RESULTS = "";
switch($test_target)
{
	case "SEQ_CREATE":
		$BENCHMARK_RESULTS = $test_parts[2];
		break;
	case "SEQ_READ":
		$BENCHMARK_RESULTS = $test_parts[4];
		break;
	case "SEQ_DELETE":
		$BENCHMARK_RESULTS = $test_parts[6];
		break;
	case "RAND_CREATE":
		$BENCHMARK_RESULTS = $test_parts[8];
		break;
	case "RAND_READ":
		$BENCHMARK_RESULTS = $test_parts[10];
		break;
	case "RAND_DELETE":
		$BENCHMARK_RESULTS = $test_parts[12];
		break;
}

echo $BENCHMARK_RESULTS;
?>
