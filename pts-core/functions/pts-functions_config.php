<?php

// Phoronix Test Suite - User Config Functions

function pts_config_init()
{
	if(!is_dir(PTS_USER_DIR))
		mkdir(PTS_USER_DIR);

	pts_user_config_init();
	pts_graph_config_init();
}
function pts_user_config_init($UserName = NULL, $UploadKey = NULL)
{
	if(is_file(PTS_USER_DIR . "user-config.xml"))
		$file = file_get_contents(PTS_USER_DIR . "user-config.xml");
	else if(is_file(ETC_DIR . "user-config-template.xml"))
		$file = file_get_contents(ETC_DIR . "user-config-template.xml");
	else
		$file = "";
	$read_config = new tandem_XmlReader($file);

	if($UserName == NULL)
		$UserName = pts_read_user_config("PhoronixTestSuite/GlobalDatabase/UserName", "Default User", $read_config);
	if($UploadKey == NULL)
		$UploadKey = pts_read_user_config("PhoronixTestSuite/GlobalDatabase/UploadKey", "", $read_config);

	$config = new tandem_XmlWriter();
	$config->addXmlObject("PhoronixTestSuite/GlobalDatabase/UserName", 0, $UserName);
	$config->addXmlObject("PhoronixTestSuite/GlobalDatabase/UploadKey", 0, $UploadKey);

	$config->addXmlObject("PhoronixTestSuite/Options/Results/Directory", 1, pts_read_user_config("PhoronixTestSuite/Options/Results/Directory", "~/.phoronix-test-suite/test-results/", $read_config));

	$config->addXmlObject("PhoronixTestSuite/Options/Benchmarking/EnvironmentDirectory", 2, pts_read_user_config("PhoronixTestSuite/Options/Benchmarking/EnvironmentDirectory", "~/.phoronix-test-suite/installed-tests/", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Options/Benchmarking/SleepTimeBetweenTests", 2, pts_read_user_config("PhoronixTestSuite/Options/Benchmarking/SleepTimeBetweenTests", "5", $read_config));

	$config->addXmlObject("PhoronixTestSuite/Options/BatchMode/SaveResults", 3, pts_read_user_config("PhoronixTestSuite/Options/BatchMode/SaveResults", "TRUE", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Options/BatchMode/OpenBrowser", 3, pts_read_user_config("PhoronixTestSuite/Options/BatchMode/OpenBrowser", "FALSE", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Options/BatchMode/UploadResults", 3, pts_read_user_config("PhoronixTestSuite/Options/BatchMode/UploadResults", "TRUE", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Options/BatchMode/PromptForTestIdentifier", 3, pts_read_user_config("PhoronixTestSuite/Options/BatchMode/PromptForTestIdentifier", "TRUE", $read_config));

	file_put_contents(PTS_USER_DIR . "user-config.xml", $config->getXML());
}
function pts_graph_config_init()
{
	if(is_file(PTS_USER_DIR . "graph-config.xml"))
		$file = file_get_contents(PTS_USER_DIR . "graph-config.xml");
	else if(is_file(RESULTS_VIEWER_DIR . "graph-config-template.xml"))
		$file = file_get_contents(RESULTS_VIEWER_DIR . "graph-config-template.xml");
	else
		$file = "";
	$read_config = new tandem_XmlReader($file);

	$config = new tandem_XmlWriter();

	// Size of Graph
	$config->addXmlObject("PhoronixTestSuite/Graphs/Size/Width", 1, pts_read_graph_config("PhoronixTestSuite/Graphs/Size/Width", "580", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/Size/Height", 1, pts_read_graph_config("PhoronixTestSuite/Graphs/Size/Height", "300", $read_config));

	// Colors
	$config->addXmlObject("PhoronixTestSuite/Graphs/Colors/Background", 2, pts_read_graph_config("PhoronixTestSuite/Graphs/Colors/Background", "#FFFFFF", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/Colors/GraphBody", 2, pts_read_graph_config("PhoronixTestSuite/Graphs/Colors/GraphBody", "#8B8F7C", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/Colors/Notches", 2, pts_read_graph_config("PhoronixTestSuite/Graphs/Colors/Notches", "#000000", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/Colors/Border", 2, pts_read_graph_config("PhoronixTestSuite/Graphs/Colors/Border", "#000000", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/Colors/Alternate", 2, pts_read_graph_config("PhoronixTestSuite/Graphs/Colors/Alternate", "#B0B59E", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/Colors/ObjectPaint", 2, pts_read_graph_config("PhoronixTestSuite/Graphs/Colors/ObjectPaint", "#3B433A, #BB2413, #FF9933, #006C00, #5028CA", $read_config));

	// Text Colors
	$config->addXmlObject("PhoronixTestSuite/Graphs/Colors/Headers", 2, pts_read_graph_config("PhoronixTestSuite/Graphs/Colors/Headers", "#2b6b29", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/Colors/MainHeaders", 2, pts_read_graph_config("PhoronixTestSuite/Graphs/Colors/MainHeaders", "#2b6b29", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/Colors/Text", 2, pts_read_graph_config("PhoronixTestSuite/Graphs/Colors/Text", "#000000", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/Colors/BodyText", 2, pts_read_graph_config("PhoronixTestSuite/Graphs/Colors/BodyText", "#FFFFFF", $read_config));

	// Text Size
	$config->addXmlObject("PhoronixTestSuite/Graphs/FontSize/Headers", 3, pts_read_graph_config("PhoronixTestSuite/Graphs/FontSize/Headers", "18", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/FontSize/SubHeaders", 3, pts_read_graph_config("PhoronixTestSuite/Graphs/FontSize/SubHeaders", "12", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/FontSize/ObjectText", 3, pts_read_graph_config("PhoronixTestSuite/Graphs/FontSize/ObjectText", "12", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/FontSize/Identifiers", 3, pts_read_graph_config("PhoronixTestSuite/Graphs/FontSize/Identifiers", "11", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/FontSize/Axis", 3, pts_read_graph_config("PhoronixTestSuite/Graphs/FontSize/Axis", "11", $read_config));

	// Text Font
	$config->addXmlObject("PhoronixTestSuite/Graphs/Font/Type", 4, pts_read_graph_config("PhoronixTestSuite/Graphs/Font/Type", "DejaVuSans.ttf", $read_config));

	// Other
	$config->addXmlObject("PhoronixTestSuite/Graphs/Other/RenderBorder", 4, pts_read_graph_config("PhoronixTestSuite/Graphs/Other/RenderBorder", "FALSE", $read_config));
	$config->addXmlObject("PhoronixTestSuite/Graphs/Other/NumberOfMarks", 4, pts_read_graph_config("PhoronixTestSuite/Graphs/Other/NumberOfMarks", "6", $read_config));

	file_put_contents(PTS_USER_DIR . "graph-config.xml", $config->getXML());
}
function pts_read_user_config($xml_pointer, $value = null, $tandem_xml = null)
{
	return pts_read_config("user-config.xml", $xml_pointer, $value, $tandem_xml);
}
function pts_read_graph_config($xml_pointer, $value = null, $tandem_xml = null)
{
	return pts_read_config("graph-config.xml", $xml_pointer, $value, $tandem_xml);
}
function pts_read_config($config_file, $xml_pointer, $value, $tandem_xml)
{
	if(!empty($tandem_xml))
	{
		$temp_value = $tandem_xml->getXmlValue($xml_pointer);

		if(!empty($temp_value))
			$value = $temp_value;
	}
	else
	{
		if(is_file(PTS_USER_DIR . $config_file))
			if(($file = file_get_contents(PTS_USER_DIR . $config_file)) != FALSE)
			{
				$xml_parser = new tandem_XmlReader($file);
				unset($file);
				$temp_value = $xml_parser->getXmlValue($xml_pointer);

				if(!empty($temp_value))
					$value = $temp_value;
			}
	}

	return $value;
}
function pts_find_home($path)
{
	if(strpos($path, "~/") !== FALSE)
	{
	/*	$whoami = trim(shell_exec("whoami"));

		if($whoami == "root")
			$home_path = "/root";
		else
			$home_path = "/home/$whoami"; */

		$home_path = pts_posix_userhome();
		$path = str_replace("~/", $home_path, $path);
	}
	return $path;
}
function pts_current_user()
{
	$pts_user = pts_read_user_config("PhoronixTestSuite/GlobalDatabase/UserName", "Default User");

	if($pts_user == "Default User")
		$pts_user = pts_posix_username();

	return $pts_user;
}

?>
