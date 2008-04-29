<?php
$arg = trim($argv[1]);
$arg = substr($arg, 0, strrpos($arg, ' '));
echo substr($arg, strrpos($arg, ' '));

?>
