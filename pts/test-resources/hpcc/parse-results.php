<?php

$log_file_name=getenv("LOG_FILE");
$log_file = file_get_contents($log_file_name);
$test_target = getenv("PTS_TEST_ARGUMENTS");

$result = trim(substr($log_file, strrpos($log_file, $test_target . "=") + strlen($test_target) + 1));
$result = substr($result, 0, strpos($result, "\n"));
list($test,$test_unit)=split("_",$test_target);
$proportion="HIB";
switch($test_unit) {
	case "Tflops":
		$unit="GFlops";
		$result*=1024;
		break;
	case "Gflops":
		$unit="Mflops";
		$result*=1024;
		break;
	case "GBs":
	case "GBytes":
	case "Triad":
	case "Add":
	case "Scale":
	case "Copy":
		$unit="MB/s";
		$result*=1024;
		break;
	case "GUPs":
		$unit="Kilo updates per second";
		$result*=1024*1024;
		break;
	case "usec":
		$unit="nsec";
		$result*=1000;
		$proportion="LIB";
		break;
	default:
		$unit=$test_unit;
		break;
}
$path=dirname($log_file_name);
file_put_contents($path.'/pts-results-scale',$unit);
file_put_contents($path.'/pts-results-proportion',$proportion);
pts_report_numeric_result($BENCHMARK_RESULTS);
?>
