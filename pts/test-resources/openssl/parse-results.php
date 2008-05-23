<?php

$BENCHMARK_RESULTS = substr($argv[1], strrpos($argv[1], "rsa 4096 bits") + 13);

$i = 0;
foreach(explode(" ", $BENCHMARK_RESULTS) as $item)
{
	$item = trim($item);

	if(!empty($item))
		$i++;

	if($i == 3)
	{
		$BENCHMARK_RESULTS = $item;
		break;
	}

}

echo $BENCHMARK_RESULTS;

?>
