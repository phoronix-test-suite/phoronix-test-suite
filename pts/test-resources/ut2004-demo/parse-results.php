<?php

$file = trim(shell_exec("cat .ut2004demo/Benchmark/benchmark.log"));
$line = substr($file, strrpos($file, "\n") + 1);
$line_r = explode("/", $line);

echo trim($line_r[1]);
?>
