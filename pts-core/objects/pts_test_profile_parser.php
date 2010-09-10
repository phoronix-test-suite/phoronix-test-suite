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

class pts_test_profile_parser
{
	protected $identifier;
	protected $xml_parser;

	public function __construct($identifier)
	{
		$this->xml_parser = new pts_test_tandem_XmlReader($identifier);
		$this->identifier = $identifier;
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
		return pts_strings::comma_explode($this->xml_parser->getXMLValue(P_TEST_EXDEP));
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
		return !in_array($this->get_status(), pts_types::test_profile_state_types());
	}
	public function get_reference_systems()
	{
		return pts_strings::comma_explode($this->xml_parser->getXMLValue(P_TEST_REFERENCE_SYSTEMS));
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
		return $this->xml_parser->getXMLValue(P_TEST_RUNCOUNT, 3);
	}
	public function get_runs_to_ignore()
	{
		return pts_strings::comma_explode($this->xml_parser->getXMLValue(P_TEST_IGNORERUNS));
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
	public function get_result_scale_formatted()
	{
		return trim(pts_strings::first_in_string($this->get_result_scale(), '|'));
	}
	public function get_result_scale_offset()
	{
		$scale_parts = explode('|', $this->get_result_scale());

		return count($scale_parts) == 2 ? trim($scale_parts[1]) : array();
	}
	public function get_result_proportion()
	{
		return $this->xml_parser->getXMLValue(P_TEST_PROPORTION);
	}
	public function get_result_format()
	{
		return $this->xml_parser->getXMLValue(P_TEST_RESULTFORMAT);
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
	public function allow_results_sharing()
	{
		return $this->xml_parser->getXMLValue(P_TEST_ALLOW_RESULTS_SHARING) != "FALSE";
	}
	public function get_min_length()
	{
		return $this->xml_parser->getXMLValue(P_TEST_MIN_LENGTH);
	}
	public function get_max_length()
	{
		return $this->xml_parser->getXMLValue(P_TEST_MAX_LENGTH);
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
		return pts_strings::comma_explode($this->xml_parser->getXMLValue(P_TEST_SUPPORTEDPLATFORMS));
	}
	public function get_supported_architectures()
	{
		return pts_strings::comma_explode($this->xml_parser->getXMLValue(P_TEST_SUPPORTEDARCHS));
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
}

?>
