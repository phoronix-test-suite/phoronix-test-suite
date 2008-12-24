<?php

$java_version = trim(shell_exec("java -version 2>&1"));

if(stripos($java_version, "Java") !== FALSE)
{
	$java_version = explode("\n", $java_version);

	if(($cut = count($java_version) - 2) > 0)
	{
		$v = trim($java_version[$cut]);
	}
	else
	{
		$v = trim(array_pop($java_version));
	}
	file_put_contents("pts-test-note", $v);
}

?>

