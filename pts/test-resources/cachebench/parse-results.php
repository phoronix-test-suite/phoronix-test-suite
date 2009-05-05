<?php

$log_file = file_get_contents(getenv("LOG_FILE"));

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

echo ($count > 0 ? $total / $count : 0);

?>
