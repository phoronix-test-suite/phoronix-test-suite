#!/bin/sh

cat > mplayer-runner.php << 'EOT'
<?php

$extensions_to_check = array("gl", "gl2", "xv", "xvmc", "vdpau");
$extensions_results = array();

foreach($extensions_to_check as $extension)
{
	echo "Checking Video Output For: $extension\n";
	$start_time = time();
	echo shell_exec(getenv("TEST_MPLAYER_BASE") . "/mplayer -vo $extension -ao null " . getenv("TEST_VIDEO_SAMPLE") . "/pts-sample-playback-1.avi");
	$end_time = time();

	$time_diff = $end_time - $start_time;

	if($time_diff < 12)
	{
		echo "\n$extension Playback FAILED!\n";
		array_push($extensions_results, "FAIL");
	}
	else
	{
		echo "\n$extension Playback PASSED!\n";
		array_push($extensions_results, "PASS");
	}
	echo "\nFinal Results: " . implode(",", $extensions_results) . "\n";
}

?>
EOT

echo "#!/bin/sh

\$PHP_BIN mplayer-runner.php > \$LOG_FILE 2>&1" > video-extensions
chmod +x video-extensions
