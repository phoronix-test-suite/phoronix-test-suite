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
		$rand_file = rand(100, 999);
		$MERGE_TO = SAVE_RESULTS_LOCATION . "merge-$rand_file.xml";
	}while(is_file($MERGE_TO));
}

// Merge Results
$MERGED_RESULTS = pts_merge_benchmarks(file_get_contents($BASE_FILE), file_get_contents($MERGE_FROM_FILE));
pts_save_result($MERGE_TO, $MERGED_RESULTS);
display_web_browser($MERGE_TO);

?>
