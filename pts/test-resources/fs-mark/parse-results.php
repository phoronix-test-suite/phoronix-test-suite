<?php

$log_file = trim(pts_read_log_file());
$log_file = substr($log_file, strrpos($log_file, "\n"));
$log_file = explode(' ', $log_file);
$value = 0;

$count = 0;
foreach($log_file as $log_comp)
{
	if(is_numeric($log_comp))
		$count++;

	if($count == 4)
	{
		$value = $log_comp;
		break;
	}
}

pts_report_numeric_result($value);

?>
