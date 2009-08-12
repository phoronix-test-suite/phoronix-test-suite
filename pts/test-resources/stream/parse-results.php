<?php

$log_file = file_get_contents(getenv("LOG_FILE"));
$test_target = getenv("PTS_TEST_ARGUMENTS");

$result = trim(substr($log_file, strrpos($log_file, $test_target . ":") + strlen($test_target) + 1));

echo substr($result, 0, strpos($result, " "));

?>
