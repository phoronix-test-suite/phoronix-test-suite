<?php

require("pts-core/functions/pts-functions.php");
require("pts-core/functions/pts-functions-merge.php");

$BASE_FILE = $argv[1];
$MERGE_FROM_FILE = $argv[2];
$MERGE_TO = $argv[3];

if(empty($BASE_FILE) || empty($MERGE_FROM_FILE))
{
	echo "\nTwo saved result profile names must be supplied.\n";
	exit;
}

if(empty($MERGE_TO))
	$MERGE_TO = $OLD_RESULTS;

$BASE_FILE = pts_find_file($BASE_FILE);
$MERGE_FROM_FILE = pts_find_file($MERGE_FROM_FILE);

if(empty($MERGE_TO))
{
	do
	{
		$rand_file = rand(1000, 9999);
		$MERGE_TO = "merge-$rand_file/";
	}while(is_dir(SAVE_RESULTS_DIR . $MERGE_TO));

	$MERGE_TO .= "composite.xml";
}

// Merge Results
$MERGED_RESULTS = pts_merge_benchmarks(file_get_contents($BASE_FILE), file_get_contents($MERGE_FROM_FILE));
pts_save_result($MERGE_TO, $MERGED_RESULTS);
display_web_browser(SAVE_RESULTS_DIR . $MERGE_TO);

?>
