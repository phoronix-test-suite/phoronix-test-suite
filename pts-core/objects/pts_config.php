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

		$read_config = new pts_config_tandem_XmlReader($new_config_values);

		$config = new tandem_XmlWriter();
		$config->setXslBinding("xsl/pts-user-config-viewer.xsl");
		$config->addXmlObjectFromReader(P_OPTION_GLOBAL_USERNAME, 0, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_GLOBAL_UPLOADKEY, 0, $read_config);

		$config->addXmlObjectFromReader(P_OPTION_USAGE_REPORTING, 1, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_SOFTWARE_REPORTING, 1, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_HARDWARE_REPORTING, 1, $read_config);

		$config->addXmlObjectFromReader(P_OPTION_DEFAULT_BROWSER, 1, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_PHODEVI_CACHE, 1, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_DISPLAY_MODE, 1, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_EXTRA_REFERENCE_SYSTEMS, 1, $read_config);

		$config->addXmlObjectFromReader(P_OPTION_LOAD_MODULES, 2, $read_config);

		$config->addXmlObjectFromReader(P_OPTION_TEST_REMOVEDOWNLOADS, 3, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_CACHE_SEARCHMEDIA, 3, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_CACHE_SYMLINK, 3, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_PROMPT_DOWNLOADLOC, 3, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_TEST_ENVIRONMENT, 3, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_CACHE_DIRECTORY, 3, $read_config);

		$config->addXmlObjectFromReader(P_OPTION_TEST_SLEEPTIME, 4, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_LOG_VSYSDETAILS, 4, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_LOG_INSTALLATION, 4, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_RESULTS_DIRECTORY, 4, $read_config);

		$config->addXmlObjectFromReader(P_OPTION_STATS_DYNAMIC_RUN_COUNT, 5, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_STATS_NO_DYNAMIC_ON_LENGTH, 5, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_STATS_STD_DEVIATION_THRESHOLD, 5, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_STATS_EXPORT_RESULTS_TO, 5, $read_config);

		$config->addXmlObjectFromReader(P_OPTION_EXTERNAL_HOOKS_PRE_TESTING, 6, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_EXTERNAL_HOOKS_INTERIM_TESTING, 6, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_EXTERNAL_HOOKS_POST_TESTING, 6, $read_config);

		$config->addXmlObjectFromReader(P_OPTION_BATCH_SAVERESULTS, 7, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_BATCH_LAUNCHBROWSER, 7, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_BATCH_UPLOADRESULTS, 7, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_BATCH_PROMPTIDENTIFIER, 7, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_BATCH_PROMPTDESCRIPTION, 7, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_BATCH_PROMPTSAVENAME, 7, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_BATCH_TESTALLOPTIONS, 7, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_BATCH_CONFIGURED, 7, $read_config);

		$config->addXmlObjectFromReader(P_OPTION_NET_NO_NETWORK, 8, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_NET_TIMEOUT, 8, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_NET_PROXY_ADDRESS, 8, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_NET_PROXY_PORT, 8, $read_config);

		$config->addXmlObjectFromReader(P_OPTION_UI_SELECT_SUITESORTESTS, 9, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_UI_SELECT_DEPENDENCIES, 9, $read_config);
		$config->addXmlObjectFromReader(P_OPTION_UI_SELECT_DOWNLOADS, 9, $read_config);

		$config->saveXMLFile(PTS_USER_PATH . "user-config.xml");
	}
	public static function graph_config_generate($new_config_values = null)
	{
		// Initialize the graph configuration file

		$read_config = new pts_graph_config_tandem_XmlReader($new_config_values);
		$config = new tandem_XmlWriter();

		// General
		$config->addXmlObjectFromReader(P_GRAPH_SIZE_WIDTH, 1, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_SIZE_HEIGHT, 1, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_RENDERER, 1, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_MARKCOUNT, 1, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_WATERMARK, 1, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_WATERMARK_URL, 1, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_BORDER, 1, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_BAR_ORIENTATION, 1, $read_config);

		$config->addXmlObjectFromReader(P_GRAPH_COLOR_BACKGROUND, 2, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_COLOR_BODY, 2, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_COLOR_NOTCHES, 2, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_COLOR_BORDER, 2, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_COLOR_ALTERNATE, 2, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_COLOR_PAINT, 2, $read_config);

		$config->addXmlObjectFromReader(P_GRAPH_COLOR_HEADERS, 2, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_COLOR_MAINHEADERS, 2, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_COLOR_TEXT, 2, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_COLOR_BODYTEXT, 2, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_COLOR_ALERT, 2, $read_config);

		$config->addXmlObjectFromReader(P_GRAPH_FONT_TYPE, 3, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_FONT_SIZE_HEADERS, 3, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_FONT_SIZE_SUBHEADERS, 3, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_FONT_SIZE_TEXT, 3, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_FONT_SIZE_IDENTIFIERS, 3, $read_config);
		$config->addXmlObjectFromReader(P_GRAPH_FONT_SIZE_AXIS, 3, $read_config);

		$config->saveXMLFile(PTS_USER_PATH . "graph-config.xml");
	}
	public static function bool_to_string($bool)
	{
		return $bool ? "TRUE" : "FALSE";
	}
	public static function read_user_config($xml_pointer, $predefined_value = false, &$tandem_xml = null)
	{
		// Generic call for reading a config file
		if($tandem_xml instanceOf tandem_XmlReader)
		{
			$read_value = $tandem_xml->getXmlValue($xml_pointer);
		}
		else
		{
			if(self::$xml_user_config == null)
			{
				self::$xml_user_config = new pts_config_tandem_XmlReader();
			}

			$read_value = self::$xml_user_config->getXmlValue($xml_pointer);
		}

		return !empty($read_value) ? $read_value : $predefined_value;
	}
	public static function read_bool_config($xml_pointer, $predefined_value = false, &$tandem_xml = null)
	{
		$value = self::read_user_config($xml_pointer, $predefined_value, $tandem_xml);
		return pts_strings::string_bool($value);
	}
	public static function read_graph_config($xml_pointer, $predefined_value = false, &$tandem_xml = null)
	{
		// Generic call for reading a config file
		if($tandem_xml instanceOf tandem_XmlReader)
		{
			$read_value = $tandem_xml->getXmlValue($xml_pointer);
		}
		else
		{
			// For now don't bother caching the graph config values since this isn't used as much as user config
			/*
			if(self::$xml_graph_config == null)
			{
				self::$xml_graph_config = new pts_graph_config_tandem_XmlReader();
			}

			$temp_value = self::$xml_graph_config->getXmlValue($xml_pointer);
			*/

			$tandem_temp = new pts_graph_config_tandem_XmlReader();
			$read_value = $tandem_temp->getXmlValue($xml_pointer);
		}

		return !empty($read_value) ? $read_value : $predefined_value;
	}
}

?>
