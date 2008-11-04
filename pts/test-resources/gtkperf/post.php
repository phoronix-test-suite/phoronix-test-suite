<?php

if(is_file("/var/log/Xorg.0.log"))
{
	$x_log = file_get_contents("/var/log/Xorg.0.log");

	if(strpos($x_log, "Using EXA") > 0)
		file_put_contents("pts-test-note", "2D Acceleration: EXA");
	else if(strpos($x_log, "Using XFree86") > 0)
		file_put_contents("pts-test-note", "2D Acceleration: XAA");
}

?>
