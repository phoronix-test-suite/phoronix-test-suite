<?php

$BENCHMARK_RESULTS = substr($argv[1], strrpos($argv[1], " 904800 "));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));
$BENCHMARK_RESULTS = explode(" ", $BENCHMARK_RESULTS);

$R_count = 0;
$result = 0;
foreach($BENCHMARK_RESULTS as $R)
{
	if(!empty($R))
	{
		$R_count++;

		if($R_count == 3 || $R_count == 5)
		{
			$result = $R;
		}
	}
}

if($result != 0)
	$result = $result / 1024;

echo $result;

?>
