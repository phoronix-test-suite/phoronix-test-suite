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
	private $results_identifier;
	private $failed_tests_to_run;

	public function __construct()
	{
		$this->tests_to_run = array();
		$this->failed_tests_to_run = array();
		$this->file_name = null;
		$this->results_identifier = null;
	}
	public function add_individual_test_run($test_identifier, $arguments = "", $descriptions = "", $override_test_options = null)
	{
		pts_arrays::unique_push($this->tests_to_run, new pts_test_run_request($test_identifier, $arguments, $descriptions, $override_test_options));
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
			$this->add_individual_test_run($test_identifier[$i], $arguments[$i], $descriptions[$i], (isset($override_test_options[$i]) ? $override_test_options[$i] : null));
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
			array_push($identifiers, $test_run_request->get_identifier());
		}

		array_unique($identifiers);

		return $identifiers;
	}
	public function get_estimated_run_time_remaining($index = 0)
	{
		$est_time = 0;

		if(isset($this->tests_to_run[$index]))
		{
			for($i = $index; $i < count($this->tests_to_run); $i++)
			{
				$test_identifier = $this->tests_to_run[$i]->get_identifier(); // is a test_run_request
				$test_time = pts_estimated_run_time($test_identifier, true, false);

				$est_time += $test_time;
			}
		}

		return $est_time;
	}
	public function get_test_to_run($index)
	{
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
	public function get_results_identifier()
	{
		return $this->results_identifier;
	}
	public function add_failed_test_run_request($test_run_request)
	{
		if($test_run_request instanceOf pts_test_run_request)
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
		$input = pts_swap_variables($input, "pts_user_runtime_variables");
		$input = trim(str_replace(array(' ', '/', '&', '?', ':', '~', '\''), null, strtolower($input)));

		return $input;
	}
	public function prompt_save_name()
	{
		// Prompt to save a file when running a test
		$proposed_name = null;
		$custom_title = null;

		if(pts_is_assignment("AUTOMATED_MODE") && ($asn = pts_read_assignment("AUTO_SAVE_NAME")))
		{
			$custom_title = $asn;
			$proposed_name = self::clean_save_name_string($asn);
			//echo "Saving Results To: " . $proposed_name . "\n";
		}
		else if(($env = pts_client::read_env("TEST_RESULTS_NAME"))
		{
			$custom_title = $enc;
			$proposed_name = self::clean_save_name_string($env);
			//echo "Saving Results To: " . $proposed_name . "\n";
		}

		if(pts_read_assignment("IS_BATCH_MODE") == false || pts_config::read_bool_config(P_OPTION_BATCH_PROMPTSAVENAME, "FALSE"))
		{
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

		pts_set_assignment_once("SAVE_FILE_NAME", $proposed_name);
		pts_set_assignment_next("PREV_SAVE_NAME_TITLE", $custom_title);
		$this->file_name = $proposed_name;

		return array($proposed_name, $custom_title);
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
				if($run_request instanceOf pts_test_run_request && in_array($run_request->get_comparison_hash(), $result_objects))
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
			$results_identifier = pts_swap_variables($results_identifier, "pts_user_runtime_variables");
		}

		pts_set_assignment_once("TEST_RESULTS_IDENTIFIER", $results_identifier);
		$this->results_identifier = $results_identifier;

		return $results_identifier;
	}
}

?>
