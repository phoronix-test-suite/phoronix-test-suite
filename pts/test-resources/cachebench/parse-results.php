<?php

$log_file = pts_read_log_file();

$total = 0;
$count = 0;

foreach(explode("\n", $log_file) as $line)
{
	$segments = explode(" ", trim($line));

	if(isset($segments[1]))
	{
		$segments[1] = trim($segments[1]);

		if(is_numeric($segments[1]))
		{
			$total += $segments[1];
			$count++;
		}
	}
}

$BENCHMARK_RESULTS = ($count > 0 ? $total / $count : 0);
pts_report_numeric_result($BENCHMARK_RESULTS);

?>
