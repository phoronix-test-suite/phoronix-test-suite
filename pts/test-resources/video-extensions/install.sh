#!/bin/sh

if [ ! -f ../pts-shared/pts-sample-playback-1.avi ]
  then
     tar -xvf ../pts-shared/pts-sample-playback-1.avi.tar.bz2 -C ../pts-shared/
fi

tar -xjf MPlayer-1.0rc2.tar.bz2

THIS_DIR=$(pwd)
mkdir $THIS_DIR/mplayer_

cd MPlayer-1.0rc2/
./configure --enable-xv --enable-xvmc --prefix=$THIS_DIR/mplayer_ > /dev/null
make -j $NUM_CPU_JOBS
make install
cd ..
rm -rf MPlayer-1.0rc2/

cat > mplayer-runner.php << 'EOT'
<?php

$extensions_to_check = array("gl", "gl2", "xv", "xvmc");
$extensions_results = array();

foreach($extensions_to_check as $extension)
{
	echo "Checking Video Output For: $extension\n";
	$start_time = time();
	echo shell_exec("./mplayer_/bin/mplayer -vo $extension -ao null ../pts-shared/pts-sample-playback-1.avi");
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
	echo "\nFinal Results: " . implode(",", $extensions_results) . ".\n";
}

?>
EOT

echo "#!/bin/sh

\$PHP_BIN mplayer-runner.php" > video-extensions
chmod +x video-extensions
