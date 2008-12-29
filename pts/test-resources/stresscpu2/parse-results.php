<?php

$log_file = trim(file_get_contents(getenv("LOG_FILE")));

if($log_file == "")
    echo "PASS";
else
    echo "FAIL";

?>
