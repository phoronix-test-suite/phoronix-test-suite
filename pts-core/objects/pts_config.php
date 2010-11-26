<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class pts_config
{
	static $xml_user_config = null;
	static $xml_graph_config = null;

	public static function init_files()
	{
		pts_config::user_config_generate();
		pts_config::graph_config_generate();
	}
	public static function user_config_generate($new_config_values = null)
	{
		// Validate the config files, update them (or write them) if needed, and other configuration file tasks

		$read_config = new pts_config_nye_XmlReader($new_config_values);

		$config = new nye_XmlWriter("xsl/pts-user-config-viewer.xsl");
		$config->addXmlNodeFromReader(P_OPTION_GLOBAL_USERNAME, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_GLOBAL_UPLOADKEY, $read_config);

		$config->addXmlNodeFromReader(P_OPTION_USAGE_REPORTING, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_SOFTWARE_REPORTING, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_HARDWARE_REPORTING, $read_config);

		$config->addXmlNodeFromReader(P_OPTION_DEFAULT_BROWSER, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_PHODEVI_CACHE, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_DISPLAY_MODE, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_EXTRA_REFERENCE_SYSTEMS, $read_config);

		$config->addXmlNodeFromReader(P_OPTION_LOAD_MODULES, $read_config);

		$config->addXmlNodeFromReader(P_OPTION_TEST_REMOVEDOWNLOADS, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_CACHE_SEARCHMEDIA, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_CACHE_SYMLINK, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_PROMPT_DOWNLOADLOC, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_TEST_ENVIRONMENT, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_CACHE_DIRECTORY, $read_config);

		$config->addXmlNodeFromReader(P_OPTION_TEST_SLEEPTIME, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_LOG_VSYSDETAILS, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_LOG_INSTALLATION, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_LOG_TEST_OUTPUT, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_RESULTS_DIRECTORY, $read_config);

		$config->addXmlNodeFromReader(P_OPTION_STATS_DYNAMIC_RUN_COUNT, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_STATS_NO_DYNAMIC_ON_LENGTH, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_STATS_STD_DEVIATION_THRESHOLD, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_STATS_EXPORT_RESULTS_TO, $read_config);

		$config->addXmlNodeFromReader(P_OPTION_EXTERNAL_HOOKS_PRE_TESTING, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_EXTERNAL_HOOKS_INTERIM_TESTING, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_EXTERNAL_HOOKS_POST_TESTING, $read_config);

		$config->addXmlNodeFromReader(P_OPTION_BATCH_SAVERESULTS, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_BATCH_LAUNCHBROWSER, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_BATCH_UPLOADRESULTS, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_BATCH_PROMPTIDENTIFIER, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_BATCH_PROMPTDESCRIPTION, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_BATCH_PROMPTSAVENAME, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_BATCH_TESTALLOPTIONS, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_BATCH_CONFIGURED, $read_config);

		$config->addXmlNodeFromReader(P_OPTION_NET_NO_NETWORK, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_NET_TIMEOUT, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_NET_PROXY_ADDRESS, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_NET_PROXY_PORT, $read_config);

		$config->addXmlNodeFromReader(P_OPTION_UI_SELECT_SUITESORTESTS, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_UI_SELECT_DEPENDENCIES, $read_config);
		$config->addXmlNodeFromReader(P_OPTION_UI_SELECT_DOWNLOADS, $read_config);

		$config->saveXMLFile(PTS_USER_PATH . "user-config.xml");
	}
	public static function graph_config_generate($new_config_values = null)
	{
		// Initialize the graph configuration file

		$read_config = new pts_graph_config_nye_XmlReader($new_config_values);
		$config = new nye_XmlWriter();

		// General
		$config->addXmlNodeFromReader(P_GRAPH_SIZE_WIDTH, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_SIZE_HEIGHT, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_RENDERER, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_MARKCOUNT, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_WATERMARK, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_WATERMARK_URL, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_BORDER, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_BAR_ORIENTATION, $read_config);

		$config->addXmlNodeFromReader(P_GRAPH_COLOR_BACKGROUND, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_COLOR_BODY, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_COLOR_NOTCHES, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_COLOR_BORDER, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_COLOR_ALTERNATE, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_COLOR_PAINT, $read_config);

		$config->addXmlNodeFromReader(P_GRAPH_COLOR_HEADERS, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_COLOR_MAINHEADERS, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_COLOR_TEXT, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_COLOR_BODYTEXT, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_COLOR_ALERT, $read_config);

		$config->addXmlNodeFromReader(P_GRAPH_FONT_TYPE, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_FONT_SIZE_HEADERS, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_FONT_SIZE_SUBHEADERS, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_FONT_SIZE_TEXT, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_FONT_SIZE_IDENTIFIERS, $read_config);
		$config->addXmlNodeFromReader(P_GRAPH_FONT_SIZE_AXIS, $read_config);

		$config->saveXMLFile(PTS_USER_PATH . "graph-config.xml");
	}
	public static function bool_to_string($bool)
	{
		return $bool ? "TRUE" : "FALSE";
	}
	public static function read_user_config($xml_pointer, $predefined_value = false, &$nye_xml = null)
	{
		// Generic call for reading a config file
		if($nye_xml instanceof nye_XmlReader)
		{
			$read_value = $nye_xml->getXmlValue($xml_pointer);
		}
		else
		{
			if(self::$xml_user_config == null)
			{
				self::$xml_user_config = new pts_config_nye_XmlReader();
			}

			$read_value = self::$xml_user_config->getXmlValue($xml_pointer);
		}

		return !empty($read_value) ? $read_value : $predefined_value;
	}
	public static function read_bool_config($xml_pointer, $predefined_value = false, &$nye_xml = null)
	{
		$value = self::read_user_config($xml_pointer, $predefined_value, $nye_xml);
		return pts_strings::string_bool($value);
	}
	public static function read_graph_config($xml_pointer, $predefined_value = false, &$nye_xml = null)
	{
		// Generic call for reading a config file
		if($nye_xml instanceof nye_XmlReader)
		{
			$read_value = $nye_xml->getXmlValue($xml_pointer);
		}
		else
		{
			// For now don't bother caching the graph config values since this isn't used as much as user config
			/*
			if(self::$xml_graph_config == null)
			{
				self::$xml_graph_config = new pts_graph_config_nye_XmlReader();
			}

			$temp_value = self::$xml_graph_config->getXmlValue($xml_pointer);
			*/

			$nye_temp = new pts_graph_config_nye_XmlReader();
			$read_value = $nye_temp->getXmlValue($xml_pointer);
		}

		return !empty($read_value) ? $read_value : $predefined_value;
	}
}

?>
