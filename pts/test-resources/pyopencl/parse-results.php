<?php

$log_file = file_get_contents(getenv("LOG_FILE"));
list($BENCHMARK_RESULTS) = split(" ",$log_file);
echo $BENCHMARK_RESULTS;

?>
