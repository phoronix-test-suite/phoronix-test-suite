<?php

$arg = trim($argv[1]);
$arg = substr($arg, 0, strrpos($arg, "Mb/s") - 1);
echo substr($arg, strrpos($arg, ' '));

?>
