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

class pts_test_profile extends pts_test_profile_parser
{
	public function __construct($identifier, $override_values = null)
	{
		parent::__construct($identifier);

		if($override_values != null && is_array($override_values))
		{
			$this->xml_parser->overrideXMLValues($override_values);
		}
	}
	public function get_override_values()
	{
		return $this->xml_parser->getOverrideValues();
	}
	public function get_download_size($include_extensions = true, $divider = 1048576)
	{
		$estimated_size = 0;

		foreach(pts_test_install_request::read_download_object_list($this->identifier) as $download_object)
		{
			$estimated_size += $download_object->get_filesize();
		}

		if($include_extensions)
		{
			$extends = $this->get_test_extension();

			if(!empty($extends))
			{
				$test_profile = new pts_test_profile($extends);
				$estimated_size += $test_profile->get_download_size(true, 1);
			}
		}

		$estimated_size = $estimated_size > 0 && $divider > 1 ? round($estimated_size / $divider, 2) : 0;

		return $estimated_size;
	}
	public function get_environment_size($include_extensions = true)
	{
		$estimated_size = parent::get_environment_size();

		if($include_extensions)
		{
			$extends = $this->get_test_extension();

			if(!empty($extends))
			{
				$test_profile = new pts_test_profile($extends);
				$estimated_size += $test_profile->get_environment_size(true);
			}
		}

		return $estimated_size;
	}
	public function get_test_extensions_recursive()
	{
		// Process Extensions / Cascading Test Profiles
		$extensions = array();
		$extended_test = $this->get_test_extension();

		if(!empty($extended_test))
		{
			do
			{
				if(!in_array($extended_test, $extensions))
				{
					array_push($extensions, $extended_test);
				}

				$extended_test = new pts_test_profile_parser($extended_test);
				$extended_test = $extended_test->get_test_extension();
			}
			while(!empty($extended_test));
		}

		return $extensions;
	}
	public function get_dependency_names()
	{
		$dependency_names = array();

		$xml_parser = new pts_external_dependencies_tandem_XmlReader(STATIC_DIR . "distro-xml/generic-packages.xml");
		$package_name = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_GENERIC);
		$title = $xml_parser->getXMLArrayValues(P_EXDEP_PACKAGE_TITLE);

		foreach($this->get_dependencies() as $dependency)
		{
			foreach(array_keys($title) as $i)
			{
				if($dependency == $package_name[$i])
				{
					array_push($dependency_names, $title[$i]);
					break;
				}
			}
		}

		return $dependency_names;
	}
	public function get_times_to_run()
	{
		$times_to_run = parent::get_times_to_run();

		if(($force_runs = pts_client::read_env("FORCE_TIMES_TO_RUN")) && is_numeric($force_runs))
		{
			$times_to_run = $force_runs;
		}

		if(($force_runs = pts_client::read_env("FORCE_MIN_TIMES_TO_RUN")) && is_numeric($force_runs) && $force_runs > $times_to_run)
		{
			$times_to_run = $force_runs;
		}

		$result_format = $this->get_result_format();
		if($times_to_run < 1 || (strlen($result_format) > 6 && substr($result_format, 0, 6) == "MULTI_" || substr($result_format, 0, 6) == "IMAGE_"))
		{
			// Currently tests that output multiple results in one run can only be run once
			$times_to_run = 1;
		}

		return $times_to_run;
	}
	public function get_estimated_run_time()
	{
		// get estimated run-time (in seconds)

		$installed_test = new pts_installed_test($this->identifier);
		$this_length = $installed_test->get_average_run_time();

		if(is_numeric($this_length) && $this_length > 0)
		{
			$estimated_time = $this_length;
		}
		else
		{
			$estimated_time = parent::get_estimated_run_time();
		}

		return $estimated_time;
	}
	public function is_supported()
	{
		return $this->is_test_architecture_supported() && $this->is_test_platform_supported() && $this->is_core_version_supported();
	}
	public function is_test_architecture_supported()
	{
		// Check if the system's architecture is supported by a test
		$supported = true;
		$archs = $this->get_supported_architectures();

		if(!empty($archs))
		{
			$supported = phodevi::cpu_arch_compatible($archs);
		}

		return $supported;
	}
	public function is_core_version_supported()
	{
		// Check if the test profile's version is compatible with pts-core
		$supported = true;
		$requires_core_version = parent::requires_core_version();

		if(!empty($requires_core_version))
		{
			$core_check = pts_strings::trim_explode('-', $requires_core_version);	
			$support_begins = $core_check[0];
			$support_ends = isset($core_check[1]) ? $core_check[1] : PTS_CORE_VERSION;
			$supported = PTS_CORE_VERSION >= $support_begins && PTS_CORE_VERSION <= $support_ends;
		}

		return $supported;
	}
	public function is_test_platform_supported()
	{
		// Check if the system's OS is supported by a test
		$supported = true;

		$platforms = $this->get_supported_platforms();

		if(!empty($platforms) && !in_array(OPERATING_SYSTEM, $platforms))
		{
			if(IS_BSD && BSD_LINUX_COMPATIBLE && in_array("Linux", $platforms))
			{
				// The OS is BSD but there is Linux API/ABI compatibility support loaded
				$supported = true;
			}
			else
			{
				$supported = false;
			}
		}

		return $supported;
	}
	public function get_installer_checksum()
	{
		// Calculate installed checksum
		$test_resources_location = pts_tests::test_resources_location($this->identifier);
		$os_postfix = '_' . strtolower(OPERATING_SYSTEM);

		if(is_file($test_resources_location . "install" . $os_postfix . ".sh"))
		{
			$md5_checksum = md5_file($test_resources_location . "install" . $os_postfix . ".sh");
		}
		else if(is_file($test_resources_location . "install.sh"))
		{
			$md5_checksum = md5_file($test_resources_location . "install.sh");
		}
		else
		{
			$md5_checksum = null;
		}

		return $md5_checksum;
	}
	public function suites_containing_test()
	{
		$associated_suites = array();

		foreach(pts_suites::available_suites() as $identifier)
		{
			if(in_array($this->identifier, pts_contained_tests($identifier)))
			{
				array_push($associated_suites, $identifier);
			}
		}

		return $associated_suites;
	}
	public static function generate_comparison_hash($test_identifier, $arguments, $attributes = null, $version = null)
	{
		$hash_table = array(
		$test_identifier,
		trim($arguments),
		trim($attributes),
		$version
		);

		return base64_encode(implode(',', $hash_table));
	}
	public function get_test_executable_dir()
	{
		$to_execute = null;
		$test_dir = TEST_ENV_DIR . $this->identifier . '/';
		$execute_binary = $this->get_test_executable();

		if(is_executable($test_dir . $execute_binary) || (IS_WINDOWS && is_file($test_dir . $execute_binary)))
		{
			$to_execute = $test_dir;
		}

		return $to_execute;
	}
}

?>
