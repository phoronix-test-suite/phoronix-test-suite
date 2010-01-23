<?php

$log_file = pts_read_log_file();
$log_file = substr($log_file, strrpos($log_file, "\nrun complete:"));
$test_target = trim(getenv("PTS_TEST_ARGUMENTS"));

switch(substr($test_target, strrpos($test_target, ' ') + 1))
{
	case "INITIAL_CREATE";
		$find = "intial create total";
		break;
	case "COMPILE";
		$find = "compile total";
		break;
	case "READ_COMPILED_TREE";
		$find = "read compiled tree";
		break;
}

$parse = substr($log_file, strpos($log_file, $find) + strlen($find));
$parse = substr($parse, 0, strpos($parse, " MB/s"));
$parse = substr($parse, strrpos($parse, ' ') + 1);
pts_report_numeric_result($parse);

/*
run complete:
==========================================================================
intial create total runs 10 avg 26.60 MB/s (user 0.46s sys 0.69s)
no runs for create
no runs for patch
compile total runs 10 avg 30.17 MB/s (user 0.11s sys 0.53s)
no runs for clean
no runs for read tree
read compiled tree total runs 3 avg 74.54 MB/s (user 0.81s sys 2.53s)
no runs for delete tree
delete compiled tree total runs 10 avg 1.14 seconds (user 0.22s sys 0.47s)
no runs for stat tree
no runs for stat compiled tree
*/

?>
