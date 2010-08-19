<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class pts_test_run_manager
{
	private $tests_to_run;
	private $file_name;
	private $file_name_title;
	private $results_identifier;
	private $failed_tests_to_run;
	private $last_test_run_index = 0;

	private $do_dynamic_run_count = false;
	private $dynamic_roun_count_on_length_or_less;
	private $dynamic_run_count_std_deviation_threshold;
	private $dynamic_run_count_export_script;

	public function __construct()
	{
		$this->tests_to_run = array();
		$this->failed_tests_to_run = array();
		$this->file_name = null;
		$this->file_name_title = null;
		$this->results_identifier = null;

		$this->do_dynamic_run_count = pts_config::read_bool_config(P_OPTION_STATS_DYNAMIC_RUN_COUNT, "TRUE");
		$this->dynamic_roun_count_on_length_or_less = pts_config::read_user_config(P_OPTION_STATS_NO_DYNAMIC_ON_LENGTH, 20);
		$this->dynamic_run_count_std_deviation_threshold = pts_config::read_user_config(P_OPTION_STATS_STD_DEVIATION_THRESHOLD, 3.50);
		$this->dynamic_run_count_export_script = pts_config::read_user_config(P_OPTION_STATS_EXPORT_RESULTS_TO, null);
	}
	public function do_dynamic_run_count()
	{
		return $this->do_dynamic_run_count;
	}
	public function increase_run_count_check(&$test_results, $test_run_time)
	{
		// Determine if results are statistically significant, otherwise up the run count
		$std_dev = pts_math::percent_standard_deviation($test_results->get_trial_results());

		if(($ex_file = $this->dynamic_run_count_export_script) != null && is_executable($ex_file) || is_executable(($ex_file = PTS_USER_DIR . $this->dynamic_run_count_export_script)))
		{
			$exit_status = trim(shell_exec($ex_file . " " . $test_results->get_trial_results_string() . " > /dev/null 2>&1; echo $?"));

			switch($exit_status)
			{
				case 1:
					// Run the test again
					$request_increase = true;
					break;
				case 2:
					// Results are bad, abandon testing and do not record results
					return -1;
				default:
					// Return was 0, results are valid, or was some other exit status
					$request_increase = false;
					break;
			}
		}
		else
		{
			$request_increase = false;
		}

		return $request_increase || $std_dev >= $this->dynamic_run_count_std_deviation_threshold && floor($test_run_time / 60) < $this->dynamic_roun_count_on_length_or_less;
	}
	public function add_individual_test_run($test_identifier, $arguments = "", $descriptions = "", $override_test_options = null)
	{
		$test_profile = new pts_test_profile($test_identifier, $override_test_options);

		$test_result = new pts_test_result($test_profile);
		$test_result->set_used_arguments($arguments);
		$test_result->set_used_arguments_description($descriptions);

		pts_arrays::unique_push($this->tests_to_run, $test_result);
	}
	public function add_single_test_run($test_identifier, $arguments, $descriptions, $override_test_options = null)
	{
		$arguments = pts_arrays::to_array($arguments);
		$descriptions = pts_arrays::to_array($descriptions);

		for($i = 0; $i < count($arguments); $i++)
		{
			$this->add_individual_test_run($test_identifier, $arguments[$i], $descriptions[$i], $override_test_options);
		}
	}
	public function add_multi_test_run($test_identifier, $arguments, $descriptions, $override_test_options = null)
	{
		$test_identifier = pts_arrays::to_array($test_identifier);
		$arguments = pts_arrays::to_array($arguments);
		$descriptions = pts_arrays::to_array($descriptions);
		$override_test_options = pts_arrays::to_array($override_test_options);

		for($i = 0; $i < count($test_identifier); $i++)
		{
			if(!empty($test_identifier[$i]))
			{
				$this->add_individual_test_run($test_identifier[$i], $arguments[$i], $descriptions[$i], (isset($override_test_options[$i]) ? $override_test_options[$i] : null));
			}
		}
	}
	protected function parse_override_test_options($override_test_options_string)
	{
		$override_options = array();

		if(!empty($override_test_options_string))
		{
			foreach(explode(";", $override_test_options_string) as $override_string)
			{
				$override_segments = pts_strings::trim_explode("=", $override_string);

				if(count($override_segments) == 2 && !empty($override_segments[0]) && !empty($override_segments[1]))
				{
					$override_options[$override_segments[0]] = $override_segments[1];
				}
			}
		}

		return $override_options;
	}
	public function add_suite_run($test_suite)
	{
		$xml_parser = new pts_suite_tandem_XmlReader($test_suite);
		$tests_in_suite = $xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME);
		$sub_modes = $xml_parser->getXMLArrayValues(P_SUITE_TEST_MODE);
		$sub_arguments = $xml_parser->getXMLArrayValues(P_SUITE_TEST_ARGUMENTS);
		$sub_arguments_description = $xml_parser->getXMLArrayValues(P_SUITE_TEST_DESCRIPTION);
		$override_test_options = $xml_parser->getXMLArrayValues(P_SUITE_TEST_OVERRIDE_OPTIONS);

		for($i = 0; $i < count($tests_in_suite); $i++)
		{
			if(pts_is_test($tests_in_suite[$i]))
			{
				$override_options = $this->parse_override_test_options($override_test_options[$i]);

				switch($sub_modes[$i])
				{
					case "BATCH":
						$option_output = pts_test_run_options::batch_user_options($tests_in_suite[$i]);
						$this->add_single_test_run($tests_in_suite[$i], $option_output[0], $option_output[1], $override_options);
						break;
					case "DEFAULTS":
						$option_output = pts_test_run_options::default_user_options($tests_in_suite[$i]);
						$this->add_single_test_run($tests_in_suite[$i], $option_output[0], $option_output[1], $override_options);
						break;
					default:
						$this->add_individual_test_run($tests_in_suite[$i], $sub_arguments[$i], $sub_arguments_description[$i], $override_options);
						break;
				}
			}
			else if(pts_is_suite($tests_in_suite[$i]))
			{
				$this->add_suite_run($tests_in_suite[$i]);
			}
		}
	}
	public function set_tests_to_run($tests_to_run)
	{
		if(is_array($tests_to_run))
		{
			$this->tests_to_run = $tests_to_run;
		}
	}
	public function get_tests_to_run()
	{
		return $this->tests_to_run;
	}
	public function get_tests_to_run_identifiers()
	{
		$identifiers = array();

		foreach($this->tests_to_run as $test_run_request)
		{
			array_push($identifiers, $test_run_request->test_profile->get_identifier());
		}

		array_unique($identifiers);

		return $identifiers;
	}
	public function get_estimated_run_time()
	{
		return $this->calculate_estimated_run_time(0);
	}
	public function get_estimated_run_time_remaining()
	{
		return $this->calculate_estimated_run_time($this->last_test_run_index);
	}
	private function calculate_estimated_run_time($index = 0)
	{
		$estimated_time = 0;

		if(isset($this->tests_to_run[$index]))
		{
			for($i = $index; $i < count($this->tests_to_run); $i++)
			{
				$identifier = $this->tests_to_run[$i]->test_profile->get_identifier(); // is a test_run_request

				$test_profile = new pts_test_profile($identifier);
				$estimated_time += $test_profile->get_estimated_run_time();
			}
		}

		return $estimated_time;
	}
	public function get_test_to_run($index)
	{
		$this->last_test_run_index = $index;
		return isset($this->tests_to_run[$index]) ? $this->tests_to_run[$index] : false;
	}
	public function get_test_count()
	{
		return count($this->tests_to_run);
	}
	public function get_file_name()
	{
		return $this->file_name;
	}
	public function get_file_name_title()
	{
		return $this->file_name_title;
	}
	public function get_results_identifier()
	{
		return $this->results_identifier;
	}
	public function add_failed_test_run_request($test_run_request)
	{
		if($test_run_request instanceOf pts_test_result)
		{
			array_push($this->failed_tests_to_run, $test_run_request);
		}
	}
	public function get_failed_test_run_requests()
	{
		return $this->failed_tests_to_run;
	}
	public static function clean_save_name_string($input)
	{
		$input = pts_strings::swap_variables($input, array("pts_client", "user_run_save_variables"));
		$input = trim(str_replace(array(' ', '/', '&', '?', ':', '~', '\''), null, strtolower($input)));

		return $input;
	}
	public function result_already_contains_identifier()
	{
		$result_file = new pts_result_file($this->file_name);

		return in_array($this->results_identifier, $result_file->get_system_identifiers());
	}
	public function prompt_save_name()
	{
		// Prompt to save a file when running a test
		$proposed_name = null;
		$custom_title = null;

		if(($asn = pts_read_assignment("AUTO_SAVE_NAME")))
		{
			$custom_title = $asn;
			$proposed_name = self::clean_save_name_string($asn);
			//echo "Saving Results To: " . $proposed_name . "\n";
		}
		else if(($env = pts_client::read_env("TEST_RESULTS_NAME")))
		{
			$custom_title = $enc;
			$proposed_name = self::clean_save_name_string($env);
			//echo "Saving Results To: " . $proposed_name . "\n";
		}

		if(pts_read_assignment("IS_BATCH_MODE") == false || pts_config::read_bool_config(P_OPTION_BATCH_PROMPTSAVENAME, "FALSE"))
		{
			$is_reserved_word = false;

			while(empty($proposed_name) || ($is_reserved_word = pts_is_run_object($proposed_name)))
			{
				if($is_reserved_word)
				{
					echo "\n\nThe name of the saved file cannot be the same as a test/suite: " . $proposed_name . "\n";
					$is_reserved_word = false;
				}

				pts_client::$display->generic_prompt("Enter a name to save these results: ");
				$proposed_name = pts_user_io::read_user_input();
				$custom_title = $proposed_name;
				$proposed_name = self::clean_save_name_string($proposed_name);
			}
		}

		if(empty($proposed_name))
		{
			$proposed_name = date("Y-m-d-Hi");
		}
		if(empty($custom_title))
		{
			$custom_title = $proposed_name;
		}

		pts_set_assignment_next("PREV_SAVE_NAME_TITLE", $custom_title);

		$this->file_name = $proposed_name;
		$this->file_name_title = $custom_title;
	}
	public function prompt_results_identifier()
	{
		// Prompt for a results identifier
		$results_identifier = null;
		$show_identifiers = array();
		$no_repeated_tests = true;

		if(pts_is_test_result($this->file_name))
		{
			$result_file = new pts_result_file($this->file_name);
			$current_identifiers = $result_file->get_system_identifiers();
			$current_hardware = $result_file->get_system_hardware();
			$current_software = $result_file->get_system_software();

			$result_objects = $result_file->get_result_objects();

			foreach(array_keys($result_objects) as $result_key)
			{
				$result_objects[$result_key] = $result_objects[$result_key]->get_comparison_hash(false);
			}

			foreach($this->tests_to_run as &$run_request)
			{
				if($run_request instanceOf pts_test_result && in_array($run_request->get_comparison_hash(), $result_objects))
				{
					$no_repeated_tests = false;
					break;
				}
			}
		}
		else
		{
			$current_identifiers = array();
			$current_hardware = array();
			$current_software = array();
		}

		if(pts_read_assignment("IS_BATCH_MODE") == false || pts_config::read_bool_config(P_OPTION_BATCH_PROMPTIDENTIFIER, "TRUE"))
		{
			if(count($current_identifiers) > 0)
			{
				echo "\nCurrent Test Identifiers:\n";
				echo pts_user_io::display_text_list($current_identifiers);
				echo "\n";
			}

			$times_tried = 0;
			do
			{
				if($times_tried == 0 && (($env_identifier = pts_client::read_env("TEST_RESULTS_IDENTIFIER")) || 
				($env_identifier = pts_read_assignment("AUTO_TEST_RESULTS_IDENTIFIER")) || pts_read_assignment("AUTOMATED_MODE")))
				{
					$results_identifier = isset($env_identifier) ? $env_identifier : null;
					echo "Test Identifier: " . $results_identifier . "\n";
				}
				else
				{
					pts_client::$display->generic_prompt("Enter a unique name for this test run: ");
					$results_identifier = trim(str_replace(array('/'), "", pts_user_io::read_user_input()));
				}
				$times_tried++;

				$identifier_pos = (($p = array_search($results_identifier, $current_identifiers)) !== false ? $p : -1);
			}
			while((!$no_repeated_tests && $identifier_pos != -1 && !pts_is_assignment("FINISH_INCOMPLETE_RUN") && !pts_is_assignment("RECOVER_RUN")) || (isset($current_hardware[$identifier_pos]) && $current_hardware[$identifier_pos] != phodevi::system_hardware(true)) || (isset($current_software[$identifier_pos]) && $current_software[$identifier_pos] != phodevi::system_software(true)));
		}

		if(empty($results_identifier))
		{
			$results_identifier = date("Y-m-d H:i");
		}
		else
		{
			$results_identifier = pts_strings::swap_variables($results_identifier, array("pts_client", "user_run_save_variables"));
		}

		$this->results_identifier = $results_identifier;
	}
}

?>
