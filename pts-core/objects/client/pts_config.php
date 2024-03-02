<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2020, Phoronix Media
	Copyright (C) 2008 - 2020, Michael Larabel

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

if(PTS_IS_CLIENT)
{
	// Upon loading pts_client, make sure files are loaded
	pts_config::init_files();
}

class pts_config
{
	static $init_process_ran = false;
	static $xml_user_config = null;
	private static $override_config_file_location = false;

	public static function get_config_file_location()
	{
		if(PTS_IS_DAEMONIZED_SERVER_PROCESS || (is_file('/etc/phoronix-test-suite.xml') && is_writable('/etc/phoronix-test-suite.xml')))
		{
			return '/etc/phoronix-test-suite.xml';
		}
		else
		{
			return PTS_USER_PATH . 'user-config.xml';
		}
	}
	public static function init_files()
	{
		// Don't let the process run multiple times...
		if(pts_config::$init_process_ran)
		{
			return false;
		}
		pts_config::$init_process_ran = true;

		// The main PTS user client config
		pts_config::user_config_generate();

		// Generate the graph config
		$json_pre = null;
		if(is_file(PTS_USER_PATH . 'graph-config.json'))
		{
			$json_pre = file_get_contents(PTS_USER_PATH . 'graph-config.json');
		}
		else if(PTS_IS_CLIENT && is_file(($t = PTS_CORE_STATIC_PATH . 'graph-config-template-' . phodevi::read_property('system', 'vendor-identifier') . '.json')))
		{
			$json_pre = file_get_contents($t);
		}
		else if(is_file(PTS_CORE_STATIC_PATH . 'graph-config-template.json'))
		{
			$json_pre = file_get_contents(PTS_CORE_STATIC_PATH . 'graph-config-template.json');
		}

		$json_graph = array();
		pts_graph_core::set_default_graph_values($json_graph);
		if($json_pre != null)
		{
			$json_pre = json_decode($json_pre, true);

			if(is_array($json_pre))
			{
				$json_graph = array_merge($json_graph, $json_pre);
			}
		}

		pts_graph_core::init_graph_config($json_graph);
		file_put_contents(PTS_USER_PATH . 'graph-config.json', pts_arrays::json_encode_pretty_string($json_graph));
	}
	public static function set_override_default_config($config_file)
	{
		self::$override_config_file_location = $config_file;
	}
	public static function get_override_default_config()
	{
		return self::$override_config_file_location;
	}
	public static function user_config_generate($new_config_values = null)
	{
		// Validate the config files, update them (or write them) if needed, and other configuration file tasks

		$read_config = new pts_config_nye_XmlReader($new_config_values, self::get_override_default_config());
		$config = new nye_XmlWriter();

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/OpenBenchmarking/AnonymousUsageReporting', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/OpenBenchmarking/IndexCacheTTL', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/OpenBenchmarking/AlwaysUploadSystemLogs', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/OpenBenchmarking/AllowResultUploadsToOpenBenchmarking', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/General/DefaultBrowser', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/General/UsePhodeviCache', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/General/DefaultDisplayMode', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/General/PhoromaticServers', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/General/ColoredConsole', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Modules/AutoLoadModules', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Installation/RemoveDownloadFiles', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Installation/SearchMediaForCache', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Installation/SymLinkFilesFromCache', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Installation/PromptForDownloadMirror', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Installation/EnvironmentDirectory', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Installation/CacheDirectory', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/SaveSystemLogs', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/SaveInstallationLogs', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/SaveTestLogs', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/SleepTimeBetweenTests', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/RemoveTestInstallOnCompletion', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/ResultsDirectory', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/AlwaysUploadResultsToOpenBenchmarking', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/AutoSortRunQueue', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Testing/ShowPostRunStatistics', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/TestResultValidation/DynamicRunCount', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/TestResultValidation/LimitDynamicToTestLength', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/TestResultValidation/StandardDeviationThreshold', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/TestResultValidation/ExportResultsTo', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/TestResultValidation/MinimalTestTime', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/TestResultValidation/DropNoisyResults', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/ResultViewer/WebPort', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/ResultViewer/LimitAccessToLocalHost', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/ResultViewer/AccessKey', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/ResultViewer/AllowSavingResultChanges', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/ResultViewer/AllowDeletingResults', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/SaveResults', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/OpenBrowser', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/UploadResults', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/PromptForTestIdentifier', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/PromptForTestDescription', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/PromptSaveName', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/RunAllTestCombinations', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/BatchMode/Configured', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Networking/NoInternetCommunication', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Networking/NoNetworkCommunication', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Networking/Timeout', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Networking/ProxyAddress', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Networking/ProxyPort', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Networking/ProxyUser', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Networking/ProxyPassword', $read_config);

		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Server/RemoteAccessPort', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Server/Password', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Server/WebSocketPort', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Server/AdvertiseServiceZeroConf', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Server/AdvertiseServiceOpenBenchmarkRelay', $read_config);
		$config->addXmlNodeFromReader('PhoronixTestSuite/Options/Server/PhoromaticStorage', $read_config);

		$config_file = pts_config::get_config_file_location();
		if($read_config->times_fallback() > 0 || !is_file($config_file))
		{
			// Something changed, so write out file, otherwise don't bother writing file
			$config->saveXMLFile($config_file);
		}
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
				self::$xml_user_config = new pts_config_nye_XmlReader(null, self::get_override_default_config());
			}

			$read_value = self::$xml_user_config->getXmlValue($xml_pointer);
		}

		if(PTS_IS_DAEMONIZED_SERVER_PROCESS)
		{
			$read_value = str_replace('~/.phoronix-test-suite/', PTS_USER_PATH, $read_value);
		}

		return (!empty($read_value) || is_numeric($read_value)) ? $read_value : $predefined_value;
	}
	public static function read_bool_config($xml_pointer, $predefined_value = false, &$nye_xml = null)
	{
		$value = self::read_user_config($xml_pointer, $predefined_value, $nye_xml);
		return pts_strings::string_bool($value);
	}
	public static function read_path_config($xml_pointer, $predefined_value = false, &$nye_xml = null)
	{
		$read_value = self::read_user_config($xml_pointer, $predefined_value, $nye_xml);
		return pts_strings::parse_for_home_directory($read_value);
	}
}

?>
