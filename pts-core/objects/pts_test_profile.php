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

class pts_test_profile
{
	private $identifier;
	private $xml_parser;

	public function __construct($identifier, $override_values = null)
	{
		$this->xml_parser = new pts_test_tandem_XmlReader($identifier);
		$this->identifier = $identifier;

		if($override_values != null && is_array($override_values))
		{
			$this->xml_parser->overrideXMLValues($override_values);
		}
	}
	public function get_identifier()
	{
		return $this->identifier;
	}
	public function get_maintainer()
	{
		return $this->xml_parser->getXMLValue(P_TEST_MAINTAINER);
	}
	public function get_test_hardware_type()
	{
		return $this->xml_parser->getXMLValue(P_TEST_HARDWARE_TYPE);
	}
	public function get_test_software_type()
	{
		return $this->xml_parser->getXMLValue(P_TEST_SOFTWARE_TYPE);
	}
	public function get_status()
	{
		return $this->xml_parser->getXMLValue(P_TEST_STATUS);
	}
	public function get_license()
	{
		return $this->xml_parser->getXMLValue(P_TEST_LICENSE);
	}
	public function get_test_profile_version()
	{
		return $this->xml_parser->getXMLValue(P_TEST_PTSVERSION);
	}
	public function get_version()
	{
		return $this->xml_parser->getXMLValue(P_TEST_VERSION);
	}
	public function get_project_url()
	{
		return $this->xml_parser->getXMLValue(P_TEST_PROJECTURL);
	}
	public function get_test_extension()
	{
		return $this->xml_parser->getXMLValue(P_TEST_CTPEXTENDS);
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
		$estimated_size = $this->xml_parser->getXMLValue(P_TEST_ENVIRONMENTSIZE);

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
	public function get_description()
	{
		return $this->xml_parser->getXMLValue(P_TEST_DESCRIPTION);
	}
	public function get_title()
	{
		return $this->xml_parser->getXMLValue(P_TEST_TITLE);
	}
	public function get_dependencies()
	{
		return pts_strings::trim_explode(",", $this->xml_parser->getXMLValue(P_TEST_EXDEP));
	}
	public function get_pre_install_message()
	{
		return $this->xml_parser->getXMLValue(P_TEST_PREINSTALLMSG);
	}
	public function get_post_install_message()
	{
		return $this->xml_parser->getXMLValue(P_TEST_POSTINSTALLMSG);
	}
	public function get_installation_agreement_message()
	{
		return $this->xml_parser->getXMLValue(P_TEST_INSTALLAGREEMENT);
	}
	public function is_verified_state()
	{
		return !in_array($this->get_status(), array("PRIVATE", "BROKEN", "EXPERIMENTAL", "UNVERIFIED"));
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
	public function get_reference_systems()
	{
		return pts_strings::trim_explode(',', $this->xml_parser->getXMLValue(P_TEST_REFERENCE_SYSTEMS));
	}
	public function get_default_arguments()
	{
		return $this->xml_parser->getXMLValue(P_TEST_DEFAULTARGUMENTS);
	}
	public function get_default_post_arguments()
	{
		return $this->xml_parser->getXMLValue(P_TEST_DEFAULT_POST_ARGUMENTS);
	}
	public function get_test_executable()
	{
		return $this->xml_parser->getXMLValue(P_TEST_EXECUTABLE, $this->identifier);
	}
	public function get_times_to_run()
	{
		return intval($this->xml_parser->getXMLValue(P_TEST_RUNCOUNT, 3));
	}
	public function get_runs_to_ignore()
	{
		return pts_strings::trim_explode(",", $this->xml_parser->getXMLValue(P_TEST_IGNORERUNS));
	}
	public function get_pre_run_message()
	{
		return $this->xml_parser->getXMLValue(P_TEST_PRERUNMSG);
	}
	public function get_post_run_message()
	{
		return $this->xml_parser->getXMLValue(P_TEST_POSTRUNMSG);
	}
	public function get_result_scale()
	{
		return $this->xml_parser->getXMLValue(P_TEST_SCALE);
	}
	public function get_result_proportion()
	{
		return $this->xml_parser->getXMLValue(P_TEST_PROPORTION);
	}
	public function get_result_format()
	{
		return $this->xml_parser->getXMLValue(P_TEST_RESULTFORMAT, "BAR_GRAPH");
	}
	public function do_auto_save_results()
	{
		return pts_strings::string_bool($this->xml_parser->getXMLValue(P_TEST_AUTO_SAVE_RESULTS, "FALSE"));
	}
	public function get_result_quantifier()
	{
		return $this->xml_parser->getXMLValue(P_TEST_QUANTIFIER);
	}
	public function is_root_required()
	{
		return $this->xml_parser->getXMLValue(P_TEST_ROOTNEEDED) == "TRUE";
	}
	public function allow_cache_share()
	{
		return $this->xml_parser->getXMLValue(P_TEST_ALLOW_CACHE_SHARE) == "TRUE";
	}
	public function allow_global_uploads()
	{
		return $this->xml_parser->getXMLValue(P_TEST_ALLOW_GLOBAL_UPLOADS) != "FALSE";
	}
	public function get_min_length()
	{
		return $this->xml_parser->getXMLValue(P_TEST_MIN_LENGTH);
	}
	public function get_max_length()
	{
		return $this->xml_parser->getXMLValue(P_TEST_MAX_LENGTH);
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
			$estimated_time = $this->xml_parser->getXMLValue(P_TEST_ESTIMATEDTIME);
		}

		return $estimated_time;
	}
	public function get_environment_testing_size()
	{
		return $this->xml_parser->getXMLValue(P_TEST_ENVIRONMENT_TESTING_SIZE, -1);
	}
	public function get_test_subtitle()
	{
		return $this->xml_parser->getXMLValue(P_TEST_SUBTITLE);
	}
	public function get_supported_platforms()
	{
		return pts_strings::trim_explode(',', $this->xml_parser->getXMLValue(P_TEST_SUPPORTEDPLATFORMS));
	}
	public function get_supported_architectures()
	{
		return pts_strings::trim_explode(',', $this->xml_parser->getXMLValue(P_TEST_SUPPORTEDARCHS));
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
		$requires_core_version = $this->xml_parser->getXMLValue(P_TEST_REQUIRES_COREVERSION);

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
	public function get_test_option_objects()
	{
		$settings_name = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_DISPLAYNAME);
		$settings_argument_prefix = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_ARGPREFIX);
		$settings_argument_postfix = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_ARGPOSTFIX);
		$settings_identifier = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_IDENTIFIER);
		$settings_default = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_DEFAULTENTRY);
		$settings_menu = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_MENU_GROUP);

		$test_options = array();

		$key_name = substr(P_TEST_OPTIONS_MENU_GROUP_NAME, strlen(P_TEST_OPTIONS_MENU_GROUP) + 1);
		$key_message = substr(P_TEST_OPTIONS_MENU_GROUP_MESSAGE, strlen(P_TEST_OPTIONS_MENU_GROUP) + 1);
		$key_value = substr(P_TEST_OPTIONS_MENU_GROUP_VALUE, strlen(P_TEST_OPTIONS_MENU_GROUP) + 1);

		foreach(array_keys($settings_name) as $option_count)
		{
			$xml_parser = new tandem_XmlReader($settings_menu[$option_count]);
			$option_names = $xml_parser->getXMLArrayValues($key_name);
			$option_messages = $xml_parser->getXMLArrayValues($key_message);
			$option_values = $xml_parser->getXMLArrayValues($key_value);
			pts_test_run_options::auto_process_test_option($this->identifier, $settings_identifier[$option_count], $option_names, $option_values, $option_messages);

			$user_option = new pts_test_option($settings_identifier[$option_count], $settings_name[$option_count]);
			$user_option->set_option_prefix($settings_argument_prefix[$option_count]);
			$user_option->set_option_postfix($settings_argument_postfix[$option_count]);

			foreach(array_keys($option_names) as $i)
			{
				$user_option->add_option($option_names[$i], $option_values[$i], (isset($option_messages[$i]) ? $option_messages[$i] : null));
			}

			$user_option->set_option_default($settings_default[$option_count]);

			array_push($test_options, $user_option);
		}

		return $test_options;
	}

	//
	// Set Functions
	//

	public function set_times_to_run($times)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_RUNCOUNT, $times);
	}
	public function set_result_scale($scale)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_SCALE, $scale);
	}
	public function set_result_proportion($proportion)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_PROPORTION, $proportion);
	}
	public function set_result_format($format)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_RESULTFORMAT, $format);
	}
	public function set_result_quantifier($quantifier)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_QUANTIFIER, $quantifier);
	}
	public function set_version($version)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_VERSION, $version);
	}
	public function set_test_title($title)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_TITLE, $title);
	}
	public function set_test_profile_version($version)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_PTSVERSION, $version);
	}
}

?>
