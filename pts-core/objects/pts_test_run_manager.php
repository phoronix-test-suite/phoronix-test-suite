<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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
		$std_dev = pts_math::percent_standard_deviation($test_results->test_result_buffer->get_values());

		if(($ex_file = $this->dynamic_run_count_export_script) != null && is_executable($ex_file) || is_executable(($ex_file = PTS_USER_DIR . $this->dynamic_run_count_export_script)))
		{
			$exit_status = trim(shell_exec($ex_file . " " . $test_results->test_result_buffer->get_values_as_string() . " > /dev/null 2>&1; echo $?"));

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
	public function call_test_runs(&$tandem_xml = null)
	{
		pts_file_io::unlink(PTS_USER_DIR . "halt-testing");
		pts_file_io::unlink(PTS_USER_DIR . "skip-test");

		$test_flag = true;
		$tests_to_run_count = $this->get_test_count();
		pts_client::$display->test_run_process_start($this);

		if(($total_loop_time_minutes = pts_client::read_env("TOTAL_LOOP_TIME")) && is_numeric($total_loop_time_minutes) && $total_loop_time_minutes > 0)
		{
			$total_loop_time_seconds = $total_loop_time_minutes * 60;
			$loop_end_time = time() + $total_loop_time_seconds;

			pts_client::$display->generic_heading("Estimated Run-Time: " . pts_strings::format_time($total_loop_time_seconds, "SECONDS", true, 60));

			do
			{
				for($i = 0; $i < $tests_to_run_count && $test_flag && time() < $loop_end_time; $i++)
				{
					$test_flag = $this->process_test_run_request($tandem_xml, $i);
				}
			}
			while(time() < $loop_end_time && $test_flag);
		}
		else if(($total_loop_count = pts_client::read_env("TOTAL_LOOP_COUNT")) && is_numeric($total_loop_count))
		{
			if(($estimated_length = $this->get_estimated_run_time()) > 1)
			{
				pts_client::$display->generic_heading("Estimated Run-Time: " . pts_strings::format_time(($estimated_length * $total_loop_count), "SECONDS", true, 60));
			}

			for($loop = 0; $loop < $total_loop_count && $test_flag; $loop++)
			{
				for($i = 0; $i < $tests_to_run_count && $test_flag; $i++)
				{
					$test_flag = $this->process_test_run_request($tandem_xml, $i, ($loop * $tests_to_run_count + $i + 1), ($total_loop_count * $tests_to_run_count));
				}
			}
		}
		else
		{
			if(($estimated_length = $this->get_estimated_run_time()) > 1)
			{
				pts_client::$display->generic_heading("Estimated Run-Time: " . pts_strings::format_time($estimated_length, "SECONDS", true, 60));
			}

			for($i = 0; $i < $tests_to_run_count && $test_flag; $i++)
			{
				$test_flag = $this->process_test_run_request($tandem_xml, $i, ($i + 1), $tests_to_run_count);
			}
		}

		pts_file_io::unlink(SAVE_RESULTS_DIR . $this->get_file_name() . "/active.xml");

		foreach(pts_file_io::glob(TEST_ENV_DIR . "*/cache-share-*.pt2so") as $cache_share_file)
		{
			// Process post-cache-share scripts
			$test_identifier = pts_extract_identifier_from_path($cache_share_file);
			echo pts_tests::call_test_script($test_identifier, "post-cache-share", null, null, pts_tests::process_extra_test_variables($test_identifier));
			unlink($cache_share_file);
		}
	}
	private function process_test_run_request(&$tandem_xml, $run_index, $run_position = -1, $run_count = -1)
	{
		$result = false;

		if($this->get_file_name() != null)
		{
			$tandem_xml->saveXMLFile(SAVE_RESULTS_DIR . $this->get_file_name() . "/active.xml");
		}

		$test_run_request = $this->get_test_to_run($run_index);

		if(pts_is_test($test_run_request->test_profile->get_identifier()))
		{
			pts_set_assignment("TEST_RUN_POSITION", $run_position);
			pts_set_assignment("TEST_RUN_COUNT", $run_count);

			if(($run_position != 1 && count(pts_file_io::glob(TEST_ENV_DIR . $test_run_request->test_profile->get_identifier() . "/cache-share-*.pt2so")) == 0))
			{
				sleep(pts_config::read_user_config(P_OPTION_TEST_SLEEPTIME, 5));
			}

			pts_test_execution::run_test($this, $test_run_request);

			if(pts_file_io::unlink(PTS_USER_DIR . "halt-testing"))
			{
				// Stop the testing process entirely
				return false;
			}
			else if(pts_file_io::unlink(PTS_USER_DIR . "skip-test"))
			{
				// Just skip the current test and do not save the results, but continue testing
				continue;
			}
		}

		$test_successful = false;


		if($test_run_request->test_profile->get_result_format() == "NO_RESULT")
		{
			$test_successful = true;
		}
		else if($test_run_request instanceof pts_test_result)
		{
			$end_result = $test_run_request->get_result();

			// removed count($result) > 0 in the move to pts_test_result
			if(count($test_run_request) > 0 && ((is_numeric($end_result) && $end_result > 0) || (!is_numeric($end_result) && isset($end_result[3]))))
			{
				$test_identifier = $this->get_results_identifier();
				$test_successful = true;

				if(!empty($test_identifier))
				{
					$tandem_id = $tandem_xml->request_unique_id();
					pts_set_assignment("TEST_RAN", true);

					$tandem_xml->addXmlObject(P_RESULTS_TEST_TITLE, $tandem_id, $test_run_request->test_profile->get_title());
					$tandem_xml->addXmlObject(P_RESULTS_TEST_VERSION, $tandem_id, $test_run_request->test_profile->get_version());
					$tandem_xml->addXmlObject(P_RESULTS_TEST_PROFILE_VERSION, $tandem_id, $test_run_request->test_profile->get_test_profile_version());
					$tandem_xml->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $tandem_id, $test_run_request->get_arguments_description());
					$tandem_xml->addXmlObject(P_RESULTS_TEST_SCALE, $tandem_id, $test_run_request->test_profile->get_result_scale());
					$tandem_xml->addXmlObject(P_RESULTS_TEST_PROPORTION, $tandem_id, $test_run_request->test_profile->get_result_proportion());
					$tandem_xml->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $tandem_id, $test_run_request->test_profile->get_result_format());
					$tandem_xml->addXmlObject(P_RESULTS_TEST_TESTNAME, $tandem_id, $test_run_request->test_profile->get_identifier());
					$tandem_xml->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $tandem_id, $test_run_request->get_arguments());
					$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $tandem_id, $test_identifier, 5);
					$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $tandem_id, $test_run_request->get_result(), 5);
					$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $tandem_id, $test_run_request->test_result_buffer->get_values_as_string(), 5);

					static $xml_write_pos = 1;
					pts_file_io::mkdir(SAVE_RESULTS_DIR . $this->get_file_name() . "/test-logs/" . $xml_write_pos . "/");

					if(is_dir(SAVE_RESULTS_DIR . $this->get_file_name() . "/test-logs/active/" . $this->get_results_identifier()))
					{
						$test_log_write_dir = SAVE_RESULTS_DIR . $this->get_file_name() . "/test-logs/" . $xml_write_pos . '/' . $this->get_results_identifier() . '/';

						if(is_dir($test_log_write_dir))
						{
							pts_file_io::delete($test_log_write_dir, null, true);
						}

						rename(SAVE_RESULTS_DIR . $this->get_file_name() . "/test-logs/active/" . $this->get_results_identifier() . '/', $test_log_write_dir);
					}
					$xml_write_pos++;
					pts_module_manager::module_process("__post_test_run_process", $tandem_xml);
				}
			}

			pts_file_io::unlink(SAVE_RESULTS_DIR . $this->get_file_name() . "/test-logs/active/");
		}

		if($test_successful == false && $test_run_request->test_profile->get_identifier() != null)
		{
			array_push($this->failed_tests_to_run, $test_run_request);

			// For now delete the failed test log files, but it may be a good idea to keep them
			pts_file_io::delete(SAVE_RESULTS_DIR . $this->get_file_name() . "/test-logs/active/" . $this->get_results_identifier() . "/", null, true);
		}

		return true;
	}
	public static function cleanup_tests_to_run(&$to_run_identifiers)
	{
		$skip_tests = ($e = pts_client::read_env("SKIP_TESTS")) ? pts_strings::comma_explode($e) : false;

		$tests_verified = array();
		$tests_missing = array();

		foreach($to_run_identifiers as &$test_identifier)
		{
			$lower_identifier = strtolower($test_identifier);

			if($skip_tests && in_array($lower_identifier, $skip_tests))
			{
				echo "Skipping Test: " . $lower_identifier . "\n";
				continue;
			}
			else if(pts_is_test($lower_identifier))
			{
				$test_profile = new pts_test_profile($lower_identifier);

				if($test_profile->get_title() == null)
				{
					echo "Not A Test: " . $lower_identifier . "\n";
					continue;
				}
				else
				{
					if(pts_client::test_support_check($lower_identifier) == false)
					{
						continue;
					}
				}
			}
			else if(pts_is_suite($lower_identifier))
			{
				$test_suite = new pts_test_suite($lower_identifier);

				if($test_suite->is_core_version_supported() == false)
				{
					echo $lower_identifier . " is a suite not supported by this version of the Phoronix Test Suite.\n";
					continue;
				}
				else if(pts_read_assignment("CONFIGURE_TESTS_IN_SUITE"))
				{
					foreach(pts_contained_tests($lower_identifier) as $test)
					{
						if(!in_array($test, $to_run_identifiers))
						{
							array_push($to_run_identifiers, $test);
						}
					}
					continue;
				}
			}
			else if(pts_is_virtual_suite($lower_identifier))
			{
				foreach(pts_virtual_suite_tests($lower_identifier) as $virt_test)
				{
					array_push($to_run_identifiers, $virt_test);
				}
				continue;
			}
			else if(!pts_is_run_object($lower_identifier) && !pts_global::is_valid_global_id_format($lower_identifier) && !pts_is_test_result($lower_identifier))
			{
				echo "Not Recognized: " . $lower_identifier . "\n";
				continue;
			}

			if(pts_test_run_manager::verify_test_installation($lower_identifier, $tests_missing) == false)
			{
				// Eliminate this test, it's not properly installed
				continue;
			}

			array_push($tests_verified, $test_identifier);
		}

		$to_run_identifiers = $tests_verified;

		if(count($tests_missing) > 0)
		{
			if(count($tests_missing) == 1)
			{
				pts_client::$display->generic_error($tests_missing[0] . " is not installed.\nTo install, run: phoronix-test-suite install " . $tests_missing[0]);
			}
			else
			{
				$message = "\n\nMultiple tests are not installed:\n\n";
				$message .= pts_user_io::display_text_list($tests_missing);
				$message .= "\nTo install, run: phoronix-test-suite install " . implode(' ', $tests_missing) . "\n\n";
				echo $message;
			}

			if(!pts_read_assignment("AUTOMATED_MODE") && !pts_read_assignment("IS_BATCH_MODE") && !pts_read_assignment("NO_PROMPT_IN_RUN_ON_MISSING_TESTS"))
			{
				$stop_and_install = pts_user_io::prompt_bool_input("Would you like to automatically install these tests now", true);

				if($stop_and_install)
				{
					pts_client::run_next("install_test", $tests_missing, pts_assignment_manager::get_all_assignments());
					pts_client::run_next("run_test", $tests_missing, pts_assignment_manager::get_all_assignments(array("NO_PROMPT_IN_RUN_ON_MISSING_TESTS" => true)));
					return false;
				}
				else
				{
					pts_set_assignment("USER_REJECTED_TEST_INSTALL_NOTICE", true);
				}
			}
		}

		return true;
	}
	public function validate_tests_to_run()
	{
		$failed_tests = array();
		$validated_run_requests = array();
		$allow_global_uploads = true;
		$display_driver = phodevi::read_property("system", "display-driver");

		foreach($this->get_tests_to_run() as $test_run_request)
		{
			if(!($test_run_request instanceOf pts_test_result))
			{
				array_push($validated_run_requests, $test_run_request);
				continue;
			}

			// Validate the empty pts_test_result
			$test_identifier = $test_run_request->test_profile->get_identifier();

			if(in_array($test_identifier, $failed_tests))
			{
				// The test has already been determined to not be installed right or other issue
				continue;
			}

			if(!is_dir(TEST_ENV_DIR . $test_identifier))
			{
				// The test is not setup
				array_push($failed_tests, $test_identifier);
				continue;
			}

			$test_type = $test_run_request->test_profile->get_test_hardware_type();

			if($test_type == "Graphics")
			{
				if(pts_client::read_env("DISPLAY") == false && !IS_WINDOWS)
				{
					pts_client::$display->test_run_error("No display server was found, cannot run " . $test_identifier);
					array_push($failed_tests, $test_identifier);
					continue;
				}
				else if(in_array($display_driver, array("vesa", "nv", "cirrus")))
				{
					pts_client::$display->test_run_error("A display driver without 3D acceleration was found, cannot run " . $test_identifier);
					array_push($failed_tests, $test_identifier);
					continue;
				}
			}

			$skip_tests = pts_client::read_env("SKIP_TESTS");
			if(pts_client::read_env("NO_" . strtoupper($test_type) . "_TESTS") || ($skip_tests && in_array($test_identifier, pts_strings::comma_explode($skip_tests))) || ($skip_tests && in_array($test_type, pts_strings::comma_explode($skip_tests))))
			{
				array_push($failed_tests, $test_identifier);
				continue;
			}

			if($test_run_request->test_profile->is_root_required() && pts_read_assignment("IS_BATCH_MODE") && phodevi::read_property("system", "username") != "root")
			{
				pts_client::$display->test_run_error("Cannot run " . $test_identifier . " in batch mode as root access is required.");
				array_push($failed_tests, $test_identifier);
				continue;
			}

			if($test_run_request->test_profile->get_test_executable_dir() == null)
			{
				pts_client::$display->test_run_error("The test executable for " . $test_identifier . " could not be found.");
				array_push($failed_tests, $test_identifier);
				continue;
			}

			if($allow_global_uploads && $test_run_request->test_profile->allow_global_uploads() == false)
			{
				// One of the contained test profiles does not allow Global uploads, so block it
				$allow_global_uploads = false;
			}

			array_push($validated_run_requests, $test_run_request);
		}

		if(!$allow_global_uploads)
		{
			pts_set_assignment("BLOCK_GLOBAL_UPLOADS", true);
		}

		$this->tests_to_run = $validated_run_requests;
	}
	protected static function verify_test_installation($identifiers, &$tests_missing)
	{
		// Verify a test is installed
		$identifiers = pts_arrays::to_array($identifiers);
		$contains_a_suite = false;
		$tests_installed = array();
		$current_tests_missing = array();

		foreach($identifiers as $identifier)
		{
			if(!$contains_a_suite && (pts_is_suite($identifier) || pts_is_test_result($identifier)))
			{
				$contains_a_suite = true;
			}

			foreach(pts_contained_tests($identifier) as $test)
			{
				if(pts_test_installed($test))
				{
					pts_arrays::unique_push($tests_installed, $test);
				}
				else
				{
					$test_profile = new pts_test_profile($test);

					if($test_profile->is_supported())
					{
						pts_arrays::unique_push($current_tests_missing, $test);
					}
				}
			}
		}

		$tests_missing = array_merge($tests_missing, $current_tests_missing);

		return count($tests_installed) > 0 && (count($current_tests_missing) == 0 || $contains_a_suite);
	}
}

?>
