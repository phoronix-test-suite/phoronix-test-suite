<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel
	pts-includes-execution.php: Functions needed for execution during only the test installation and run processes

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

function pts_call_test_script($test_identifier, $script_name, $print_string = null, $pass_argument = null, $extra_vars = null, $use_ctp = true)
{
	$result = null;
	$test_directory = TEST_ENV_DIR . $test_identifier . "/";

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
function pts_test_update_install_xml($identifier, $this_duration = 0, $is_install = false)
{
	// Refresh/generate an install XML for pts-install.xml
 	$xml_parser = new pts_installed_test_tandem_XmlReader($identifier, false);
	$xml_writer = new tandem_XmlWriter();
	$xml_writer->setXslBinding("file://" . PTS_USER_DIR . "xsl/" . "pts-test-installation-viewer.xsl");

	$test_duration = $xml_parser->getXMLValue(P_INSTALL_TEST_AVG_RUNTIME);
	if(!is_numeric($test_duration) && !$is_install)
	{
		$test_duration = $this_duration;
	}
	if(!$is_install && is_numeric($this_duration) && $this_duration > 0)
	{
		$test_duration = ceil((($test_duration * $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN)) + $this_duration) / ($xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN) + 1));
	}

	$test_version = $is_install ? pts_test_profile_version($identifier) : $xml_parser->getXMLValue(P_INSTALL_TEST_VERSION);
	$test_checksum = $is_install ? pts_test_checksum_installer($identifier) : $xml_parser->getXMLValue(P_INSTALL_TEST_CHECKSUM);
	$sys_identifier = $is_install ? phodevi::system_id_string() : $xml_parser->getXMLValue(P_INSTALL_TEST_SYSIDENTIFY);
	$install_time = $is_install ? date("Y-m-d H:i:s") : $xml_parser->getXMLValue(P_INSTALL_TEST_INSTALLTIME);
	$install_time_length = $is_install ? $this_duration : $xml_parser->getXMLValue(P_INSTALL_TEST_INSTALLTIME_LENGTH);
	$latest_run_time = $is_install || $this_duration == 0 ? $xml_parser->getXMLValue(P_INSTALL_TEST_LATEST_RUNTIME) : $this_duration;

	$times_run = $xml_parser->getXMLValue(P_INSTALL_TEST_TIMESRUN);
	if(empty($times_run))
	{
		$times_run = 0;
	}

	if($is_install)
	{
		$last_run = $xml_parser->getXMLValue(P_INSTALL_TEST_LASTRUNTIME);

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

?>
