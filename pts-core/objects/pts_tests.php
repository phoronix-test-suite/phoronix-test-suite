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
	public static function installed_tests()
	{
		$cleaned_tests = array();

		foreach(pts_file_io::glob(PTS_TEST_INSTALL_PATH . '*/') as $repo_path)
		{
			$repo = basename($repo_path);
			foreach(pts_file_io::glob($repo . '/*') as $identifier_path)
			{
				if(is_file($identifier_path . '/test-definition.xml'))
				{
					$identifier = basename($identifier_path);

					array_push($cleaned_tests, $repo . '/' . $identifier);
				}
			}
		}

		return $cleaned_tests;
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
	public static function extra_environmental_variables(&$test_profile)
	{
		$extra_vars = array();
		$extra_vars['HOME'] = $test_profile->get_install_dir();
		$extra_vars['PATH'] = "\$PATH";
		$extra_vars['LC_ALL'] = '';
		$extra_vars['LC_NUMERIC'] = '';
		$extra_vars['LC_CTYPE'] = '';
		$extra_vars['LC_MESSAGES'] = '';
		$extra_vars['LANG'] = '';
		$extra_vars['vblank_mode'] = '0';
		$extra_vars['PHP_BIN'] = PHP_BIN;

		foreach($test_profile->extended_test_profiles() as $i => $this_test_profile)
		{
			if($i == 0)
			{
				$extra_vars['TEST_EXTENDS'] = $this_test_profile->get_install_dir();
			}

			if(is_dir($this_test_profile->get_install_dir()))
			{
				$extra_vars['PATH'] = $this_test_profile->get_install_dir() . ':' . $extra_vars['PATH'];
				$extra_vars['TEST_' . strtoupper(str_replace('-', '_', $this_test_profile->get_identifier_base_name()))] = $this_test_profile->get_install_dir();
			}
		}

		return $extra_vars;
	}
	public static function call_test_script($test_profile, $script_name, $print_string = null, $pass_argument = null, $extra_vars_append = null, $use_ctp = true)
	{
		$extra_vars = pts_tests::extra_environmental_variables($test_profile);
		if(is_array($extra_vars_append))
		{
			$extra_vars = array_merge($extra_vars, $extra_vars_append);
		}

		// TODO: call_test_script could be better cleaned up to fit more closely with new pts_test_profile functions
		$result = null;
		$test_directory = $test_profile->get_install_dir();
		$os_postfix = '_' . strtolower(OPERATING_SYSTEM);
		$test_profiles = array($test_profile);

		if($use_ctp)
		{
			$test_profiles = array_merge($test_profiles, $test_profile->extended_test_profiles());
		}

		foreach($test_profiles as &$this_test_profile)
		{
			$test_resources_location = $this_test_profile->get_resource_dir();

			if(is_file(($run_file = $test_resources_location . $script_name . $os_postfix . '.sh')) || is_file(($run_file = $test_resources_location . $script_name . '.sh')))
			{
				if(!empty($print_string))
				{
					pts_client::$display->test_run_message($print_string);
				}

				if(IS_WINDOWS || pts_client::read_env('USE_PHOROSCRIPT_INTERPRETER') != false)
				{
					$phoroscript = new pts_phoroscript_interpreter($run_file, $extra_vars, $test_directory);
					$phoroscript->execute_script($pass_argument);
				}
				else
				{
					$this_result = pts_client::shell_exec('cd ' .  $test_directory . ' && sh ' . $run_file . ' "' . $pass_argument . '" 2>&1', $extra_vars);
				}

				if(trim($this_result) != null)
				{
					$result = $this_result;
				}
			}
		}

		return $result;
	}
	public static function update_test_install_xml(&$test_profile, $this_duration = 0, $is_install = false)
	{
		// Refresh/generate an install XML for pts-install.xml
		$installed_test = new pts_installed_test($test_profile);
		$xml_writer = new nye_XmlWriter('file://' . PTS_USER_PATH . 'xsl/' . 'pts-test-installation-viewer.xsl');

		$test_duration = $installed_test->get_average_run_time();
		if(!is_numeric($test_duration) && !$is_install)
		{
			$test_duration = $this_duration;
		}
		if(!$is_install && is_numeric($this_duration) && $this_duration > 0)
		{
			$test_duration = ceil((($test_duration * $installed_test->get_run_count()) + $this_duration) / ($installed_test->get_run_count() + 1));
		}

		$test_version = $is_install ? $test_profile->get_test_profile_version() : $installed_test->get_installed_version();
		$test_checksum = $is_install ? $test_profile->get_installer_checksum() : $installed_test->get_installed_checksum();
		$sys_identifier = $is_install ? phodevi::system_id_string() : $installed_test->get_installed_system_identifier();
		$install_time = $is_install ? date('Y-m-d H:i:s') : $installed_test->get_install_date_time();
		$install_time_length = $is_install ? $this_duration : $installed_test->get_latest_install_time();
		$latest_run_time = $is_install || $this_duration == 0 ? $installed_test->get_latest_run_time() : $this_duration;

		$times_run = $installed_test->get_run_count();

		if($is_install)
		{
			$last_run = $latest_run_time;

			if(empty($last_run))
			{
				$last_run = '0000-00-00 00:00:00';
			}
		}
		else
		{
			$last_run = date('Y-m-d H:i:s');
			$times_run++;
		}

		$xml_writer->addXmlNode(P_INSTALL_TEST_NAME, $test_profile->get_identifier());
		$xml_writer->addXmlNode(P_INSTALL_TEST_VERSION, $test_version);
		$xml_writer->addXmlNode(P_INSTALL_TEST_CHECKSUM, $test_checksum);
		$xml_writer->addXmlNode(P_INSTALL_TEST_SYSIDENTIFY, $sys_identifier);
		$xml_writer->addXmlNode(P_INSTALL_TEST_INSTALLTIME, $install_time);
		$xml_writer->addXmlNode(P_INSTALL_TEST_INSTALLTIME_LENGTH, $install_time_length);
		$xml_writer->addXmlNode(P_INSTALL_TEST_LASTRUNTIME, $last_run);
		$xml_writer->addXmlNode(P_INSTALL_TEST_TIMESRUN, $times_run);
		$xml_writer->addXmlNode(P_INSTALL_TEST_AVG_RUNTIME, $test_duration);
		$xml_writer->addXmlNode(P_INSTALL_TEST_LATEST_RUNTIME, $latest_run_time);

		$xml_writer->saveXMLFile($test_profile->get_install_dir() . 'pts-install.xml');
	}
}

?>
