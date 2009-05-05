<?php

$file = @file_get_contents(getenv("LOG_FILE"));
$results = trim(substr($file, strrpos($file, "Requests per second:") + 22));
$results = trim(substr($results, 0, strpos($results, " ")));

echo $results;

?>
