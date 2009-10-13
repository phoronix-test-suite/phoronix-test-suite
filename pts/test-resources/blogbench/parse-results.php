<?php

$log_file = pts_read_log_file();

if(trim(getenv("PTS_TEST_ARGUMENTS")) == "WRITE")
{
	$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Final score for writes"));
}
else
{
	$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Final score for reads"));
}

$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, strpos($BENCHMARK_RESULTS, ":") + 1);
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));

pts_report_numeric_result($BENCHMARK_RESULTS);

?>
