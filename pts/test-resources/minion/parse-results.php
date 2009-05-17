<?php

$file = @file_get_contents(getenv("LOG_FILE"));
$results = substr($file, strrpos($file, "Total Wall Time: ") + 17);
$results = substr($results, 0, strpos($results, "\n"));

echo $results;

?>
