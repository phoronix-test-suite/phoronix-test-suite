<?php

$log_file = pts_read_log_file();
$test_target = getenv("PTS_TEST_ARGUMENTS");

switch($test_target)
{
	case "TRADITIONAL_DES_MANY_SALTS":
		$find_keyword = "Traditional DES";
		break;
	case "MD5":
		$find_keyword = "FreeBSD MD5";
		break;
	case "BLOWFISH":
		$find_keyword = "OpenBSD Blowfish";
		break;
	default:
		$find_keyword = "FAILED";
		break;

}

$log_file = substr($log_file, strpos($log_file, $find_keyword));
$log_file = substr($log_file, strpos($log_file, ":") + 1);
$result = trim(substr($log_file, 0, strpos($log_file, "c/s")));


if(substr($result, -1) == "K")
{
	$result = substr($result, 0, -1) . "000";
}

pts_report_numeric_result($result);

?>
