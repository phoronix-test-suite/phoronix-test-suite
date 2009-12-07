<?php

$test_target = trim(getenv("PTS_TEST_ARGUMENTS"));

switch($test_target)
{
	case "nexuiz000085.tga":
		shell_exec("\$IQC_IMPORT_IMAGE \$HOME/.nexuiz/data/screenshots/nexuiz000085.tga 645 679 540 300");
		break;
	case "nexuiz000090.tga":
		shell_exec("\$IQC_IMPORT_IMAGE \$HOME/.nexuiz/data/screenshots/nexuiz000090.tga 420 322 350 310");
		break;
	case "nexuiz000112.tga":
		shell_exec("\$IQC_IMPORT_IMAGE \$HOME/.nexuiz/data/screenshots/nexuiz000112.tga 600 345 440 270");
		break;
}

$no_remove = array("nexuiz000085.tga", "nexuiz000090.tga", "nexuiz000112.tga");
foreach(glob(getenv("HOME") . ".nexuiz/data/screenshots/*") as $screenshot)
{
	if(!in_array(basename($screenshot), $no_remove))
	{
		unlink($screenshot);
	}
}

?>
