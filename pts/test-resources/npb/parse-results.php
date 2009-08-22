<?php

$log_file = file_get_contents(getenv("LOG_FILE"));
//$test_target = getenv("PTS_TEST_ARGUMENTS");

$result = substr($log_file, strrpos($log_file, "Mop/s total     =") + 19);
$result = trim(substr($result, 0, strpos($result, "\n")));

echo $result;

?>
