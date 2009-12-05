<?php

$log_file = pts_read_log_file();
$fps_r = array();
$to_find = "Current FPS: ";

while(($x = strpos($log_file, $to_find)) > 0)
{
	$log_file = substr($log_file, $x + strlen($to_find));
	$fps = substr($log_file, 0, strpos($log_file, "\n"));
	array_push($fps_r, $fps);
}

pts_report_line_graph_array($fps_r);
?>
