<?php

// find the line with "reclean" on it
$BENCHMARK_RESULTS = substr($argv[1], strrpos($argv[1], "reclen"));
// skip to the next line
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 1+strpos($BENCHMARK_RESULTS, "\n"));
// remove stuff after this line
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, 0, strpos($BENCHMARK_RESULTS, "\n"));

// break up the line into columns based on whitespace
$BENCHMARK_RESULTS = explode(" ", $BENCHMARK_RESULTS);

$R_count = 0;
$result = 0;

/* This loop picks out the result value from either the 3rd or the 5th
   column. If both columns contain a value then the 5th column is
   used, which is the read time. If only the 3rd column contains a
   value then it is used as the write time.

Example:

    KB  reclen   write rewrite    read    reread    read   write ...
512000       1   47591   19718   129991   106731
*/

foreach($BENCHMARK_RESULTS as $R)
{
	if(!empty($R))
	{
		$R_count++;

		if($R_count == 3 || $R_count == 5)
		{
			$result = $R;
		}
	}
}

if($result != 0)
	$result = $result / 1024;

echo $result;

?>
