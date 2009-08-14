<?php

$file = @file_get_contents(getenv("LOG_FILE"));
$results = trim(substr($file, strrpos($file, "Totals:") + 8));
$results = substr($results, 0, strpos($results, "ms"));

echo $results;

?>
