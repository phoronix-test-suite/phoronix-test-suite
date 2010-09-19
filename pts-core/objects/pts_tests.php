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

class pts_tests
{
	public static function available_tests()
	{
		static $cache = null;

		if($cache == null)
		{
			$tests = glob(XML_PROFILE_DIR . "*.xml");

			if($tests == false)
			{
				$tests = array();
			}

			asort($tests);

			foreach($tests as &$test)
			{
				$test = basename($test, ".xml");
			}

			$cache = $tests;
		}

		return $cache;
	}
	public static function installed_tests()
	{
		if(!pts_is_assignment("CACHE_INSTALLED_TESTS"))
		{
			$cleaned_tests = array();

			foreach(pts_file_io::glob(TEST_ENV_DIR . "*/pts-install.xml") as $test)
			{
				$test = pts_extract_identifier_from_path($test);

				if(pts_is_test($test))
				{
					array_push($cleaned_tests, $test);
				}
			}

			pts_set_assignment("CACHE_INSTALLED_TESTS", $cleaned_tests);
		}

		return pts_read_assignment("CACHE_INSTALLED_TESTS");
	}
	public static function supported_tests()
	{
		static $cache = null;

		if($cache == null)
		{
			$supported_tests = array();

			foreach(pts_tests::available_tests() as $identifier)
			{
				$test_profile = new pts_test_profile($identifier);

				if($test_profile->is_supported())
				{
					array_push($supported_tests, $identifier);
				}
			}

			$cache = $supported_tests;
		}

		return $cache;
	}
	public static function test_profile_location($identifier, $rewrite_cache = false)
	{
		static $cache;

		if(!isset($cache[$identifier]) || $rewrite_cache)
		{
			switch(pts_identifier_type($identifier))
			{
				case "TYPE_TEST":
					$location = XML_PROFILE_DIR . $identifier . ".xml";
					break;
				case "TYPE_BASE_TEST":
					$location = XML_PROFILE_CTP_BASE_DIR . $identifier . ".xml";
					break;
				default:
					$location = false;
					break;
			}

			$cache[$identifier] = $location;
		}

		return $cache[$identifier];
	}
	public static function test_resources_location($identifier, $rewrite_cache = false)
	{
		static $cache;

		if(!isset($cache[$identifier]) || $rewrite_cache)
		{
			$type = pts_identifier_type($identifier);

			if($type == "TYPE_BASE_TEST" && is_dir(TEST_RESOURCE_CTP_BASE_DIR . $identifier))
			{
				$location = TEST_RESOURCE_CTP_BASE_DIR . $identifier . "/";
			}
			else if(is_dir(TEST_RESOURCE_DIR . $identifier))
			{
				$location = TEST_RESOURCE_DIR . $identifier . "/";
			}
			else
			{
				$location = false;
			}

			$cache[$identifier] = $location;
		}

		return $cache[$identifier];
	}
	public static function test_hardware_type($test_identifier)
	{
		static $cache;

		if(!isset($cache[$test_identifier]))
		{
			$test_profile = new pts_test_profile($test_identifier);
			$test_subsystem = $test_profile->get_test_hardware_type();
			$cache[$test_identifier] = $test_subsystem;
			unset($test_profile);
		}

		return $cache[$test_identifier];
	}
	public static function process_extra_test_variables($identifier)
	{
		$extra_vars = array();
		$extra_vars["HOME"] = TEST_ENV_DIR . $identifier . "/";

		$ctp_extension_string = "";

		$test_profile = new pts_test_profile($identifier);
		$extends = $test_profile->get_test_extensions_recursive();

		if(isset($extends[0]))
		{
			$extra_vars["TEST_EXTENDS"] = TEST_ENV_DIR . $extends[0];
		}

		foreach(array_merge(array($identifier), $extends) as $extended_test)
		{
			if(is_dir(TEST_ENV_DIR . $extended_test . "/"))
			{
				$ctp_extension_string .= TEST_ENV_DIR . $extended_test . ":";
				$extra_vars["TEST_" . strtoupper(str_replace("-", "_", $extended_test))] = TEST_ENV_DIR . $extended_test;
			}
		}

		if(!empty($ctp_extension_string))
		{
			$extra_vars["PATH"] = $ctp_extension_string . "\$PATH";
		}

		return $extra_vars;
	}
	public static function call_test_script($test_identifier, $script_name, $print_string = null, $pass_argument = null, $extra_vars = null, $use_ctp = true)
	{
		$result = null;
		$test_directory = TEST_ENV_DIR . $test_identifier . '/';

		$tests_r = ($use_ctp ? pts_contained_tests($test_identifier, true) : array($test_identifier));

		foreach($tests_r as &$this_test)
		{
			$test_resources_location = pts_tests::test_resources_location($this_test);
			$os_postfix = '_' . strtolower(OPERATING_SYSTEM);

			if(is_file($test_resources_location . $script_name . $os_postfix . ".php") || is_file($test_resources_location . $script_name . $os_postfix . ".sh"))
			{
				$script_name .= $os_postfix;
			}

			if(is_file(($run_file = $test_resources_location . $script_name . ".php")) || is_file(($run_file = $test_resources_location . $script_name . ".sh")))
			{
				$file_extension = substr($run_file, (strrpos($run_file, ".") + 1));

				if(!empty($print_string))
				{
					pts_client::$display->test_run_message($print_string);
				}

				if($file_extension == "php")
				{
					$this_result = pts_client::shell_exec("cd " .  $test_directory . " && " . PHP_BIN . " " . $run_file . " \"" . $pass_argument . "\" 2>&1", $extra_vars);
				}
				else if($file_extension == "sh")
				{
					if(IS_WINDOWS || pts_client::read_env("USE_PHOROSCRIPT_INTERPRETER") != false)
					{
						$phoroscript = new pts_phoroscript_interpreter($run_file, $extra_vars, $test_directory);
						$phoroscript->execute_script($pass_argument);
					}
					else
					{
						$this_result = pts_client::shell_exec("cd " .  $test_directory . " && sh " . $run_file . " \"" . $pass_argument . "\" 2>&1", $extra_vars);
					}
				}
				else
				{
					$this_result = null;
				}

				if(trim($this_result) != "")
				{
					$result = $this_result;
				}
			}
		}

		return $result;
	}
	public static function update_test_install_xml($identifier, $this_duration = 0, $is_install = false)
	{
		// Refresh/generate an install XML for pts-install.xml
		$installed_test = new pts_installed_test($identifier);
		$xml_writer = new tandem_XmlWriter();
		$xml_writer->setXslBinding("file://" . PTS_USER_DIR . "xsl/" . "pts-test-installation-viewer.xsl");

		$test_duration = $installed_test->get_average_run_time();
		if(!is_numeric($test_duration) && !$is_install)
		{
			$test_duration = $this_duration;
		}
		if(!$is_install && is_numeric($this_duration) && $this_duration > 0)
		{
			$test_duration = ceil((($test_duration * $installed_test->get_run_count()) + $this_duration) / ($installed_test->get_run_count() + 1));
		}

		$test_profile = new pts_test_profile($identifier);

		$test_version = $is_install ? $test_profile->get_test_profile_version() : $installed_test->get_installed_version();
		$test_checksum = $is_install ? $test_profile->get_installer_checksum() : $installed_test->get_installed_checksum();
		$sys_identifier = $is_install ? phodevi::system_id_string() : $installed_test->get_installed_system_identifier();
		$install_time = $is_install ? date("Y-m-d H:i:s") : $installed_test->get_install_date_time();
		$install_time_length = $is_install ? $this_duration : $installed_test->get_latest_install_time();
		$latest_run_time = $is_install || $this_duration == 0 ? $installed_test->get_latest_run_time() : $this_duration;

		$times_run = $installed_test->get_run_count();

		if($is_install)
		{
			$last_run = $latest_run_time;

			if(empty($last_run))
			{
				$last_run = "0000-00-00 00:00:00";
			}
		}
		else
		{
			$last_run = date("Y-m-d H:i:s");
			$times_run++;
		}

		$xml_writer->addXmlObject(P_INSTALL_TEST_NAME, 1, $identifier);
		$xml_writer->addXmlObject(P_INSTALL_TEST_VERSION, 1, $test_version);
		$xml_writer->addXmlObject(P_INSTALL_TEST_CHECKSUM, 1, $test_checksum);
		$xml_writer->addXmlObject(P_INSTALL_TEST_SYSIDENTIFY, 1, $sys_identifier);
		$xml_writer->addXmlObject(P_INSTALL_TEST_INSTALLTIME, 2, $install_time);
		$xml_writer->addXmlObject(P_INSTALL_TEST_INSTALLTIME_LENGTH, 2, $install_time_length);
		$xml_writer->addXmlObject(P_INSTALL_TEST_LASTRUNTIME, 2, $last_run);
		$xml_writer->addXmlObject(P_INSTALL_TEST_TIMESRUN, 2, $times_run);
		$xml_writer->addXmlObject(P_INSTALL_TEST_AVG_RUNTIME, 2, $test_duration);
		$xml_writer->addXmlObject(P_INSTALL_TEST_LATEST_RUNTIME, 2, $latest_run_time);

		$xml_writer->saveXMLFile(TEST_ENV_DIR . $identifier . "/pts-install.xml");
	}
}

?>
