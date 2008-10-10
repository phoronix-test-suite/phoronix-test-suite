<?php

$log_file = file_get_contents(getenv("LOG_FILE"));
$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "rsa 4096 bits") + 13);

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
