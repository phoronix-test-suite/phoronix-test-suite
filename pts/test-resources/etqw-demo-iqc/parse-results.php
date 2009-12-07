<?php

$test_target = trim(getenv("PTS_TEST_ARGUMENTS"));

switch($test_target)
{
	case "vegetation":
		shell_exec("\$IQC_IMPORT_IMAGE \$HOME/shot00001.tga 484 105 332 256");
		break;
	case "boat":
		shell_exec("\$IQC_IMPORT_IMAGE \$HOME/shot00002.tga 380 320 360 290");
		break;
	case "boat2":
		shell_exec("\$IQC_IMPORT_IMAGE \$HOME/shot00003.tga 660 310 306 374");
		break;
	case "water":
		shell_exec("\$IQC_IMPORT_IMAGE \$HOME/shot00004.tga 285 465 320 220");
		break;
	case "trees":
		shell_exec("\$IQC_IMPORT_IMAGE \$HOME/shot00005.tga 500 184 247 131");
		break;
}

?>
