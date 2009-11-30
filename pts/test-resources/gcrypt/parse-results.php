<?php

/*
Example output
Running each test 50 times.
             ECB             CBC             CFB             OFB             CTR             STREAM         
             --------------- --------------- --------------- --------------- --------------- ---------------
CAMELLIA256   1730ms  1760ms  1820ms  1850ms  1800ms  1830ms  1810ms  1810ms  2620ms  2620ms          
*/

$log_file = pts_read_log_file();
$BENCHMARK_RESULTS = substr($log_file, 0, strpos($log_file, "ms"));
$BENCHMARK_RESULTS = substr($BENCHMARK_RESULTS, strrpos($BENCHMARK_RESULTS, " ") + 1);
pts_report_numeric_result($BENCHMARK_RESULTS);

?>
