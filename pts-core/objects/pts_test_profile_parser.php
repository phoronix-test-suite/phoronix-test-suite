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
	public $xml_parser;

	public function __construct($identifier = null)
	{
		if(PTS_IS_CLIENT)
		{
			$identifier = pts_openbenchmarking::evaluate_string_to_qualifier($identifier);
		}

		$this->xml_parser = new pts_test_nye_XmlReader($identifier);

		if(!isset($identifier[64]))
		{
			// Passed is not an identifier since it's too long
			$this->identifier = $identifier;
		}
	}
	public function __toString()
	{
		return $this->get_identifier();
	}
	public function get_identifier($bind_version = true)
	{
		$identifier = $this->identifier;

		if($bind_version == false && ($c = strrpos($identifier, '-')))
		{
			$identifier = substr($identifier, 0, $c);
		}

		return $identifier;
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
	public function get_app_version()
	{
		return $this->xml_parser->getXMLValue(P_TEST_VERSION);
	}
	public function get_project_url()
	{
		return $this->xml_parser->getXMLValue(P_TEST_PROJECTURL);
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
	public function get_internal_tags_raw()
	{
		return $this->xml_parser->getXMLValue(P_TEST_INTERNAL_TAGS);
	}
	public function get_internal_tags()
	{
		return pts_strings::comma_explode($this->get_internal_tags_raw());
	}
	public function get_default_arguments()
	{
		return $this->xml_parser->getXMLValue(P_TEST_DEFAULTARGUMENTS);
	}
	public function get_default_post_arguments()
	{
		return $this->xml_parser->getXMLValue(P_TEST_DEFAULT_POST_ARGUMENTS);
	}
	public function get_identifier_base_name()
	{
		// TODO: clean this up
		$identifier = basename($this->identifier);
		$identifier = ($s = strrpos($identifier, '-')) !== false ? substr($identifier, 0, $s) : $identifier;

		return $identifier;
	}
	public function get_test_executable()
	{
		return $this->xml_parser->getXMLValue(P_TEST_EXECUTABLE, $this->get_identifier_base_name());
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
	public function get_display_format()
	{
		return $this->xml_parser->getXMLValue(P_TEST_DISPLAY_FORMAT, "BAR_GRAPH");
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
		return pts_strings::string_bool($this->xml_parser->getXMLValue(P_TEST_ROOTNEEDED, "FALSE"));
	}
	public function allow_cache_share()
	{
		return pts_strings::string_bool($this->xml_parser->getXMLValue(P_TEST_ALLOW_CACHE_SHARE, "FALSE"));
	}
	public function allow_results_sharing()
	{
		return pts_strings::string_bool($this->xml_parser->getXMLValue(P_TEST_ALLOW_RESULTS_SHARING, "TRUE"));
	}
	public function get_min_length()
	{
		return $this->xml_parser->getXMLValue(P_TEST_MIN_LENGTH);
	}
	public function get_max_length()
	{
		return $this->xml_parser->getXMLValue(P_TEST_MAX_LENGTH);
	}
	public function get_test_subtitle()
	{
		return $this->xml_parser->getXMLValue(P_TEST_SUBTITLE);
	}
	public function get_supported_platforms_raw()
	{
		return $this->xml_parser->getXMLValue(P_TEST_SUPPORTEDPLATFORMS);
	}
	public function get_supported_platforms()
	{
		return pts_strings::comma_explode($this->get_supported_platforms_raw());
	}
	public function get_supported_architectures()
	{
		return pts_strings::comma_explode($this->xml_parser->getXMLValue(P_TEST_SUPPORTEDARCHS));
	}
	public function get_environment_size()
	{
		return $this->xml_parser->getXMLValue(P_TEST_ENVIRONMENTSIZE, 0);
	}
	public function get_test_extension()
	{
		return $this->xml_parser->getXMLValue(P_TEST_CTPEXTENDS);
	}
	public function get_environment_testing_size()
	{
		return $this->xml_parser->getXMLValue(P_TEST_ENVIRONMENT_TESTING_SIZE, 0);
	}
	public function get_estimated_run_time()
	{
		return $this->xml_parser->getXMLValue(P_TEST_EST_TIME_PER_RUN, 0) * $this->get_times_to_run();
	}
	public function requires_core_version_min()
	{
		return $this->xml_parser->getXMLValue(P_TEST_REQUIRES_COREVERSION_MIN, 2950);
	}
	public function requires_core_version_max()
	{
		return $this->xml_parser->getXMLValue(P_TEST_REQUIRES_COREVERSION_MAX, 9190);
	}
	public function get_test_option_objects($auto_process = true)
	{
		$settings_name = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_DISPLAYNAME);
		$settings_argument_prefix = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_ARGPREFIX);
		$settings_argument_postfix = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_ARGPOSTFIX);
		$settings_identifier = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_IDENTIFIER);
		$settings_default = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_DEFAULTENTRY);
		$option_names = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_MENU_GROUP_NAME, 1);
		$option_messages = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_MENU_GROUP_MESSAGE, 1);
		$option_values = $this->xml_parser->getXMLArrayValues(P_TEST_OPTIONS_MENU_GROUP_VALUE, 1);
		$test_options = array();

		foreach(array_keys($settings_name) as $option_count)
		{
			$names = $option_names[$option_count];
			$messages = $option_messages[$option_count];
			$values = $option_values[$option_count];

			if($auto_process)
			{
				pts_test_run_options::auto_process_test_option($this->identifier, $settings_identifier[$option_count], $names, $values, $messages);
			}

			$user_option = new pts_test_option($settings_identifier[$option_count], $settings_name[$option_count]);
			$user_option->set_option_prefix($settings_argument_prefix[$option_count]);
			$user_option->set_option_postfix($settings_argument_postfix[$option_count]);

			for($i = 0; $i < count($names); $i++)
			{
				$user_option->add_option($names[$i], (isset($values[$i]) ? $values[$i] : null), (isset($messages[$i]) ? $messages[$i] : null));
			}

			$user_option->set_option_default($settings_default[$option_count]);

			array_push($test_options, $user_option);
		}

		return $test_options;
	}
	public function get_reference_id()
	{
		// This isn't needed for test profiles, but keep this here for compatibility when passing a test_profile to pts_result_file_writer
		return null;
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
	public function set_display_format($format)
	{
		$this->xml_parser->overrideXMLValue(P_TEST_DISPLAY_FORMAT, $format);
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
	public function set_identifier($identifier)
	{
		$this->identifier = $identifier;
	}
}

?>
