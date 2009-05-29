<?php
$log_file = file_get_contents(getenv("LOG_FILE"));

if(trim(getenv("PTS_TEST_ARGUMENTS")) == "WRITE")
{
	$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Final score for writes"));
}
else
{
	$BENCHMARK_RESULTS = substr($log_file, strrpos($log_file, "Final score for reads"));
}

$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, strpos($BENCHMARK_RESULTS, ":") + 1);
echo substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));
?>
