<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_config.php: Functions needed to read/write to the PTS user-configuration files.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

function pts_config_init()
{
	if(!is_dir(PTS_USER_DIR))
	{
		mkdir(PTS_USER_DIR);
	}
	if(!is_dir(PTS_TEMP_DIR))
	{
		mkdir(PTS_TEMP_DIR);
	}

	pts_user_config_init();
	pts_graph_config_init();
}
function pts_user_config_init($UserName = null, $UploadKey = null, $BatchOptions = null)
{
	// Validate the config files, update them (or write them) if needed, and other configuration file tasks

	if(is_file(PTS_USER_DIR . "user-config.xml"))
	{
		$file = file_get_contents(PTS_USER_DIR . "user-config.xml");
	}
	else if(is_file(ETC_DIR . "user-config-template.xml"))
	{
		$file = file_get_contents(ETC_DIR . "user-config-template.xml");
	}
	else
	{
		$file = "";
	}

	$read_config = new tandem_XmlReader($file);

	$UserAgreement = pts_read_user_config(P_OPTION_USER_AGREEMENT, "", $read_config);
	$UserAgreement_MD5 = md5_file("pts-core/user-agreement.txt");

	if($UserAgreement != $UserAgreement_MD5)
	{
		echo pts_string_header("PHORONIX TEST SUITE - WELCOME");
		echo wordwrap(file_get_contents("pts-core/user-agreement.txt"), 65);
		$agree = pts_bool_question("Do you agree to these terms and wish to proceed (Y/n)?", true);

		if($agree)
		{
			echo "\n";
		}
		else
		{
			pts_exit(pts_string_header("In order to run the Phoronix Test Suite, you must agree to the listed terms."));
		}
	}

	if($UserName == null)
	{
		$UserName = pts_read_user_config(P_OPTION_GLOBAL_USERNAME, "Default User", $read_config);
	}
	if($UploadKey == null)
	{
		$UploadKey = pts_read_user_config(P_OPTION_GLOBAL_UPLOADKEY, "", $read_config);
	}

	if($BatchOptions == null || !is_array($BatchOptions))
	{
		$BatchOptions = array();
		$BatchOptions[0] = pts_read_user_config(P_OPTION_BATCH_SAVERESULTS, "TRUE", $read_config);
		$BatchOptions[1] = pts_read_user_config(P_OPTION_BATCH_LAUNCHBROWSER, "FALSE", $read_config);
		$BatchOptions[2] = pts_read_user_config(P_OPTION_BATCH_UPLOADRESULTS, "TRUE", $read_config);
		$BatchOptions[3] = pts_read_user_config(P_OPTION_BATCH_PROMPTIDENTIFIER, "TRUE", $read_config);
		$BatchOptions[4] = pts_read_user_config(P_OPTION_BATCH_PROMPTDESCRIPTION, "TRUE", $read_config);
		$BatchOptions[5] = pts_read_user_config(P_OPTION_BATCH_PROMPTSAVENAME, "TRUE", $read_config);
		$BatchOptions[6] = pts_read_user_config(P_OPTION_BATCH_CONFIGURED, "FALSE", $read_config);
	}
	else
	{
		$BatchOptions[0] = pts_config_bool_to_string($BatchOptions[0]);
		$BatchOptions[1] = pts_config_bool_to_string($BatchOptions[1]);
		$BatchOptions[2] = pts_config_bool_to_string($BatchOptions[2]);
		$BatchOptions[3] = pts_config_bool_to_string($BatchOptions[3]);
		$BatchOptions[4] = pts_config_bool_to_string($BatchOptions[4]);
		$BatchOptions[5] = pts_config_bool_to_string($BatchOptions[5]);
		$BatchOptions[6] = "TRUE";
	}

	// Determine last version run of the Phoronix Test Suite
	$last_version = pts_read_user_config(P_OPTION_TESTCORE_LASTVERSION, PTS_VERSION, $read_config);
	$last_time = pts_read_user_config(P_OPTION_TESTCORE_LASTTIME, date("Y-m-d H:i:s"), $read_config);
	if(defined("PTS_END_TIME"))
	{
		$last_version = PTS_VERSION;
		$last_time = date("Y-m-d H:i:s");
	}

	$config = new tandem_XmlWriter();
	$config->addXmlObject(P_OPTION_GLOBAL_USERNAME, 0, $UserName);
	$config->addXmlObject(P_OPTION_GLOBAL_UPLOADKEY, 0, $UploadKey);

	$config->addXmlObject(P_OPTION_LOAD_MODULES, 2, pts_read_user_config(P_OPTION_LOAD_MODULES, "", $read_config));

	$config->addXmlObject(P_OPTION_TEST_REMOVEDOWNLOADS, 3, pts_read_user_config(P_OPTION_TEST_REMOVEDOWNLOADS, "FALSE", $read_config));
	$config->addXmlObject(P_OPTION_CACHE_SEARCHMEDIA, 3, pts_read_user_config(P_OPTION_CACHE_SEARCHMEDIA, "TRUE", $read_config));
	$config->addXmlObject(P_OPTION_PROMPT_DOWNLOADLOC, 3, pts_read_user_config(P_OPTION_PROMPT_DOWNLOADLOC, "FALSE", $read_config));
	$config->addXmlObject(P_OPTION_TEST_ENVIRONMENT, 3, pts_read_user_config(P_OPTION_TEST_ENVIRONMENT, "~/.phoronix-test-suite/installed-tests/", $read_config));
	$config->addXmlObject(P_OPTION_CACHE_DIRECTORY, 3, pts_read_user_config(P_OPTION_CACHE_DIRECTORY, "~/.phoronix-test-suite/download-cache/", $read_config));
	$config->addXmlObject(P_OPTION_SCTP_DIRECTORY, 3, pts_read_user_config(P_OPTION_SCTP_DIRECTORY, "~/.phoronix-test-suite/sctp/", $read_config));

	$config->addXmlObject(P_OPTION_TEST_SLEEPTIME, 4, pts_read_user_config(P_OPTION_TEST_SLEEPTIME, "8", $read_config));
	$config->addXmlObject(P_OPTION_LOG_VSYSDETAILS, 4, pts_read_user_config(P_OPTION_LOG_VSYSDETAILS, "FALSE", $read_config));
	$config->addXmlObject(P_OPTION_LOG_BENCHMARKFILES, 4, pts_read_user_config(P_OPTION_LOG_BENCHMARKFILES, "FALSE", $read_config));
	$config->addXmlObject(P_OPTION_RESULTS_DIRECTORY, 4, pts_read_user_config(P_OPTION_RESULTS_DIRECTORY, "~/.phoronix-test-suite/test-results/", $read_config));

	$config->addXmlObject(P_OPTION_BATCH_SAVERESULTS, 5, $BatchOptions[0]);
	$config->addXmlObject(P_OPTION_BATCH_LAUNCHBROWSER, 5, $BatchOptions[1]);
	$config->addXmlObject(P_OPTION_BATCH_UPLOADRESULTS, 5, $BatchOptions[2]);
	$config->addXmlObject(P_OPTION_BATCH_PROMPTIDENTIFIER, 5, $BatchOptions[3]);
	$config->addXmlObject(P_OPTION_BATCH_PROMPTDESCRIPTION, 5, $BatchOptions[4]);
	$config->addXmlObject(P_OPTION_BATCH_PROMPTSAVENAME, 5, $BatchOptions[5]);
	$config->addXmlObject(P_OPTION_BATCH_CONFIGURED, 5, $BatchOptions[6]);

	$config->addXmlObject(P_OPTION_TESTCORE_LASTVERSION, 6, $last_version);
	$config->addXmlObject(P_OPTION_TESTCORE_LASTTIME, 6, $last_time);
	$config->addXmlObject(P_OPTION_USER_AGREEMENT, 7, $UserAgreement_MD5);

	file_put_contents(PTS_USER_DIR . "user-config.xml", $config->getXML());
}
function pts_module_config_init($SetOptions = null)
{
	// Validate the config files, update them (or write them) if needed, and other configuration file tasks

	if(is_file(PTS_USER_DIR . "modules-config.xml"))
	{
		$file = file_get_contents(PTS_USER_DIR . "modules-config.xml");
	}
	else
	{
		$file = "";
	}

	$module_config_parser = new tandem_XmlReader($file);
	$option_module = $module_config_parser->getXMLArrayValues(P_MODULE_OPTION_NAME);
	$option_identifier = $module_config_parser->getXMLArrayValues(P_MODULE_OPTION_IDENTIFIER);
	$option_value = $module_config_parser->getXMLArrayValues(P_MODULE_OPTION_VALUE);

	if(is_array($SetOptions) && count($SetOptions) > 0)
	{
		foreach($SetOptions as $this_option_set => $this_option_value)
		{
			$replaced = false;
			$this_option_set = explode("__", $this_option_set);
			$this_option_module = $this_option_set[0];
			$this_option_identifier = $this_option_set[1];

			for($i = 0; $i < count($option_module) && !$replaced; $i++)
			{
				if($option_module[$i] == $this_option_module && $option_identifier[$i] == $this_option_identifier)
				{
					$option_value[$i] = $this_option_value;
					$replaced = true;
				}
			}

			if(!$replaced)
			{
				array_push($option_module, $this_option_module);
				array_push($option_identifier, $this_option_identifier);
				array_push($option_value, $this_option_value);
			}
		}
	}

	$config = new tandem_XmlWriter();

	for($i = 0; $i < count($option_module); $i++)
	{
		if(pts_module_type($option_module[$i]) != "")
		{
			$config->addXmlObject(P_MODULE_OPTION_NAME, $i, $option_module[$i]);
			$config->addXmlObject(P_MODULE_OPTION_IDENTIFIER, $i, $option_identifier[$i]);
			$config->addXmlObject(P_MODULE_OPTION_VALUE, $i, $option_value[$i]);
		}
	}

	file_put_contents(PTS_USER_DIR . "modules-config.xml", $config->getXML());
}
function pts_config_bool_to_string($bool)
{
	// Evaluate a string to boolean type
	if($bool == true)
	{
		$bool_return = "TRUE";
	}
	else
	{
		$bool_return = "FALSE";
	}

	return $bool_return;
}
function pts_graph_config_init()
{
	// Initialize the graph configuration file

	if(is_file(PTS_USER_DIR . "graph-config.xml"))
	{
		$file = file_get_contents(PTS_USER_DIR . "graph-config.xml");
	}
	else if(is_file(RESULTS_VIEWER_DIR . "graph-config-template.xml"))
	{
		$file = file_get_contents(RESULTS_VIEWER_DIR . "graph-config-template.xml");
	}
	else
	{
		$file = "";
	}

	$read_config = new tandem_XmlReader($file);
	$config = new tandem_XmlWriter();

	// Size of Graph
	$config->addXmlObject(P_GRAPH_SIZE_WIDTH, 1, pts_read_graph_config(P_GRAPH_SIZE_WIDTH, "580", $read_config));
	$config->addXmlObject(P_GRAPH_SIZE_HEIGHT, 1, pts_read_graph_config(P_GRAPH_SIZE_HEIGHT, "300", $read_config));

	// Colors
	$config->addXmlObject(P_GRAPH_COLOR_BACKGROUND, 2, pts_read_graph_config(P_GRAPH_COLOR_BACKGROUND, "#FFFFFF", $read_config));
	$config->addXmlObject(P_GRAPH_COLOR_BODY, 2, pts_read_graph_config(P_GRAPH_COLOR_BODY, "#8B8F7C", $read_config));
	$config->addXmlObject(P_GRAPH_COLOR_NOTCHES, 2, pts_read_graph_config(P_GRAPH_COLOR_NOTCHES, "#000000", $read_config));
	$config->addXmlObject(P_GRAPH_COLOR_BORDER, 2, pts_read_graph_config(P_GRAPH_COLOR_BORDER, "#FFFFFF", $read_config));
	$config->addXmlObject(P_GRAPH_COLOR_ALTERNATE, 2, pts_read_graph_config(P_GRAPH_COLOR_ALTERNATE, "#B0B59E", $read_config));
	$config->addXmlObject(P_GRAPH_COLOR_PAINT, 2, pts_read_graph_config(P_GRAPH_COLOR_PAINT, "#3B433A, #BB2413, #FF9933, #006C00, #5028CA", $read_config));

	// Text Colors
	$config->addXmlObject(P_GRAPH_COLOR_HEADERS, 2, pts_read_graph_config(P_GRAPH_COLOR_HEADERS, "#2b6b29", $read_config));
	$config->addXmlObject(P_GRAPH_COLOR_MAINHEADERS, 2, pts_read_graph_config(P_GRAPH_COLOR_MAINHEADERS, "#2b6b29", $read_config));
	$config->addXmlObject(P_GRAPH_COLOR_TEXT, 2, pts_read_graph_config(P_GRAPH_COLOR_TEXT, "#000000", $read_config));
	$config->addXmlObject(P_GRAPH_COLOR_BODYTEXT, 2, pts_read_graph_config(P_GRAPH_COLOR_BODYTEXT, "#FFFFFF", $read_config));

	// Text Size
	$config->addXmlObject(P_GRAPH_FONT_SIZE_HEADERS, 3, pts_read_graph_config(P_GRAPH_FONT_SIZE_HEADERS, "18", $read_config));
	$config->addXmlObject(P_GRAPH_FONT_SIZE_SUBHEADERS, 3, pts_read_graph_config(P_GRAPH_FONT_SIZE_SUBHEADERS, "12", $read_config));
	$config->addXmlObject(P_GRAPH_FONT_SIZE_TEXT, 3, pts_read_graph_config(P_GRAPH_FONT_SIZE_TEXT, "12", $read_config));
	$config->addXmlObject(P_GRAPH_FONT_SIZE_IDENTIFIERS, 3, pts_read_graph_config(P_GRAPH_FONT_SIZE_IDENTIFIERS, "11", $read_config));
	$config->addXmlObject(P_GRAPH_FONT_SIZE_AXIS, 3, pts_read_graph_config(P_GRAPH_FONT_SIZE_AXIS, "11", $read_config));

	// Text Font
	$config->addXmlObject(P_GRAPH_FONT_TYPE, 4, pts_read_graph_config(P_GRAPH_FONT_TYPE, "Sans.ttf", $read_config));

	// Other
	$config->addXmlObject(P_GRAPH_RENDERBORDER, 4, pts_read_graph_config(P_GRAPH_RENDERBORDER, "FALSE", $read_config));
	$config->addXmlObject(P_GRAPH_MARKCOUNT, 4, pts_read_graph_config(P_GRAPH_MARKCOUNT, "6", $read_config));
	$config->addXmlObject(P_GRAPH_WATERMARK, 4, pts_read_graph_config(P_GRAPH_WATERMARK, "PHORONIX-TEST-SUITE.COM", $read_config));
	$config->addXmlObject(P_GRAPH_BORDER, 4, pts_read_graph_config(P_GRAPH_BORDER, "FALSE", $read_config));

	file_put_contents(PTS_USER_DIR . "graph-config.xml", $config->getXML());
}
function pts_read_user_config($xml_pointer, $value = null, $tandem_xml = null)
{
	// Read an option from a user's config file
	return pts_read_config("user-config.xml", $xml_pointer, $value, $tandem_xml);
}
function pts_read_graph_config($xml_pointer, $value = null, $tandem_xml = null)
{
	// Read an option from a user's graph config file
	return pts_read_config("graph-config.xml", $xml_pointer, $value, $tandem_xml);
}
function pts_read_config($config_file, $xml_pointer, $value, $tandem_xml)
{
	// Generic call for reading a config file
	if(!empty($tandem_xml))
	{
		$temp_value = $tandem_xml->getXmlValue($xml_pointer);

		if(!empty($temp_value))
		{
			$value = $temp_value;
		}
	}
	else
	{
		if(is_file(PTS_USER_DIR . $config_file))
		{
			if(($file = file_get_contents(PTS_USER_DIR . $config_file)) != false)
			{
				$xml_parser = new tandem_XmlReader($file);
				unset($file);
				$temp_value = $xml_parser->getXmlValue($xml_pointer);

				if(!empty($temp_value))
				{
					$value = $temp_value;
				}
			}
		}
	}

	return $value;
}
function pts_find_home($path)
{
	// Find home directory if needed
	if(strpos($path, "~/") !== false)
	{
		$home_path = pts_user_home();
		$path = str_replace("~/", $home_path, $path);
	}
	return $path;
}
function pts_user_home()
{
	// Gets the system user's home directory
	if(function_exists("posix_getpwuid") && function_exists("posix_getuid"))
	{
		$userinfo = posix_getpwuid(posix_getuid());
		$userhome = $userinfo["dir"];
	}
	else
	{
		$userhome = getenv("HOME");
	}

	return $userhome . "/";
}
function pts_current_user()
{
	// Current system user
	$pts_user = pts_read_user_config(P_OPTION_GLOBAL_USERNAME, "Default User");

	if($pts_user == "Default User")
	{
		$pts_user = sw_os_username();
	}

	return $pts_user;
}
function pts_download_cache()
{
	// Returns directory of the PTS Download Cache
	$dir = getenv("PTS_DOWNLOAD_CACHE");

	if(empty($dir))
	{
		$dir = pts_read_user_config(P_OPTION_CACHE_DIRECTORY, "~/.phoronix-test-suite/download-cache/");
	}

	if(substr($dir, -1) != '/')
	{
			$dir .= '/';
	}

	return $dir;
}

?>
