<?php

$log_file = trim(pts_read_log_file());

if($log_file == "")
    echo "PASS";
else
    echo "FAIL";

?>
