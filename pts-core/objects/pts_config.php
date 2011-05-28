<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel

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

		$config = new nye_XmlWriter('xsl/pts-user-config-viewer.xsl');

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/OpenBenchmarking/AnonymousUsageReporting', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/OpenBenchmarking/AnonymousSoftwareReporting', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/OpenBenchmarking/AnonymousHardwareReporting', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/OpenBenchmarking/IndexCacheTTL', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/OpenBenchmarking/AlwaysUploadSystemLogs', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/General/DefaultBrowser', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/General/UsePhodeviCache', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/General/DefaultDisplayMode', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Modules/LoadModules', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Installation/RemoveDownloadFiles', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Installation/SearchMediaForCache', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Installation/SymLinkFilesFromCache', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Installation/PromptForDownloadMirror', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Installation/EnvironmentDirectory', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Installation/CacheDirectory', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/SaveSystemLogs', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/SaveInstallationLogs', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/SaveTestLogs', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/RemoveTestInstallOnCompletion', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/ResultsDirectory', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/AlwaysUploadResultsToOpenBenchmarking', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/TestResultValidation/DynamicRunCount', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/TestResultValidation/LimitDynamicToTestLength', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/TestResultValidation/StandardDeviationThreshold', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/TestResultValidation/ExportResultsTo', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/SaveResults', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/OpenBrowser', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/UploadResults', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/PromptForTestIdentifier', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/PromptForTestDescription', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/PromptSaveName', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/RunAllTestCombinations', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/Configured', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Networking/NoNetworkCommunication', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Networking/Timeout', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Networking/ProxyAddress', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Networking/ProxyPort', $read_config);

		$config->saveXMLFile(PTS_USER_PATH . 'user-config.xml');
	}
	public static function graph_config_generate($new_config_values = null)
	{
		// Initialize the graph configuration file

		$read_config = new pts_graph_config_nye_XmlReader($new_config_values);
		$config = new nye_XmlWriter();

		// General
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/General/GraphWidth', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/General/GraphHeight', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/General/Renderer', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/General/Watermark', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/General/WatermarkURL', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/General/Border', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/General/BarOrientation', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Colors/Background', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Colors/GraphBody', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Colors/Notches', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Colors/Border', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Colors/Alternate', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Colors/ObjectPaint', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Colors/Headers', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Colors/MainHeaders', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Colors/Text', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Colors/BodyText', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Colors/Highlight', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Colors/Alert', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Font/FontType', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Font/Headers', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Font/SubHeaders', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Font/ObjectText', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Font/Identifiers', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Graphs/Font/Axis', $read_config);

		$config->saveXMLFile(PTS_USER_PATH . 'graph-config.xml');
	}
	public static function bool_to_string($bool)
	{
		return $bool ? 'TRUE' : 'FALSE';
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
