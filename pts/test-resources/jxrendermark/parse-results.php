<?php
$log_file = file_get_contents(getenv("LOG_FILE"));
$results = trim(substr($log_file, 0, strpos($log_file, "Ops/s")));
echo $results;
?>
