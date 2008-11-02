<?php

$log_file = file_get_contents(getenv("LOG_FILE"));
$fps_values = array();

foreach(explode("\n", $log_file) as $log_line)
{
	$log_value = substr($log_line, strpos($log_line, "= ") + 2);
	$log_value = substr($log_value, 0, strpos($log_value, " FPS"));

	if(is_numeric($log_value))
	{
		array_push($fps_values, $log_value);
	}
}

if(count($fps_values) > 0)
	echo (array_sum($fps_values) / count($fps_values));

?>
