<?php

$log_file = pts_read_log_file();
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

pts_report_numeric_result($BENCHMARK_RESULTS);

?>
