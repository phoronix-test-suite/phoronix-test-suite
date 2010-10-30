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
	public $result_file_writer = null;

	private $tests_to_run = array();
	private $failed_tests_to_run = array();
	private $last_test_run_index = 0;
	private $completed_runs = 0;

	private $file_name = null;
	private $file_name_title = null;
	private $results_identifier = null;
	private $run_description = null;

	private $force_save_results = false;
	private $prompt_save_results = true;
	private $post_run_message = null;
	private $pre_run_message = null;
	private $allow_sharing_of_results = true;
	private $auto_upload_to_global = false;
	private $is_pcqs = false;

	private $do_dynamic_run_count = false;
	private $dynamic_roun_count_on_length_or_less;
	private $dynamic_run_count_std_deviation_threshold;
	private $dynamic_run_count_export_script;

	// These variables could potentially be cleaned up or eliminated
	private $results_directory;
	private $wrote_system_xml;

	private static $user_rejected_install_notice = false;
	private static $test_run_process_active = false;

	public function __construct($test_flags = 0)
	{
		pts_client::set_test_flags($test_flags);

		$this->do_dynamic_run_count = pts_config::read_bool_config(P_OPTION_STATS_DYNAMIC_RUN_COUNT, "TRUE");
		$this->dynamic_roun_count_on_length_or_less = pts_config::read_user_config(P_OPTION_STATS_NO_DYNAMIC_ON_LENGTH, 20);
		$this->dynamic_run_count_std_deviation_threshold = pts_config::read_user_config(P_OPTION_STATS_STD_DEVIATION_THRESHOLD, 3.50);
		$this->dynamic_run_count_export_script = pts_config::read_user_config(P_OPTION_STATS_EXPORT_RESULTS_TO, null);

		pts_module_manager::module_process("__run_manager_setup", $this);
	}
	public function is_pcqs()
	{
		return $this->is_pcqs;
	}
	public function do_dynamic_run_count()
	{
		return $this->do_dynamic_run_count;
	}
	public function auto_upload_to_global($do = true)
	{
		$this->auto_upload_to_global = ($do == true);
	}
	public function increase_run_count_check(&$test_results, $scheduled_times_to_run, $latest_test_run_time)
	{
		// First make sure this test doesn't take too long to run where we don't want dynamic handling
		if(floor($latest_test_run_time / 60) > $this->dynamic_roun_count_on_length_or_less)
		{
			return false;
		}

		// Determine if results are statistically significant, otherwise up the run count
		$std_dev = pts_math::percent_standard_deviation($test_results->test_result_buffer->get_values());
		if($std_dev >= $this->dynamic_run_count_std_deviation_threshold)
		{
			static $last_run_count = 128; // just a number that should always cause the first check below to be true
			static $run_std_devs;
			$times_already_ran = $test_results->test_result_buffer->get_count();

			if($times_already_ran <= $last_run_count)
			{
				// We're now onto a new test so clear out the array
				$run_std_devs = array();
			}
			$last_run_count = $times_already_ran;
			$run_std_devs[$last_run_count] = $std_dev;

			// If we haven't reached scheduled times to run x 2, increase count straight away
			if($times_already_ran < ($scheduled_times_to_run * 2))
			{
				return true;
			}
			else if($times_already_ran < ($scheduled_times_to_run * 3))
			{
				// More aggressive determination whether to still keep increasing the run count
				$first_and_now_diff = pts_arrays::first_element($run_std_devs) - pts_arrays::last_element($run_std_devs);

				// Increasing the run count at least looks to be helping...
				if($first_and_now_diff > (pts_arrays::first_element($run_std_devs) / 2))
				{
					// If we are at least making progress in the right direction, increase the run count some more
					return true;
				}

				// TODO: could add more checks and take better advantage of the array of data to better determine if it's still worth increasing
			}

		}

		// Check to see if there is an external/custom script to export the results to in determining whether results are valid
		if(($ex_file = $this->dynamic_run_count_export_script) != null && is_executable($ex_file) || is_executable(($ex_file = PTS_USER_DIR . $this->dynamic_run_count_export_script)))
		{
			$exit_status = trim(shell_exec($ex_file . " " . $test_results->test_result_buffer->get_values_as_string() . " > /dev/null 2>&1; echo $?"));

			switch($exit_status)
			{
				case 1:
					// Run the test again
					return true;
				case 2:
					// Results are bad, abandon testing and do not record results
					return -1;
				case 0:
				default:
					// Return was 0 or something else, results are valid, or was some other exit status
					break;
			}
		}

		// No reason to increase the run count evidently
		return false;
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
	public function force_results_save()
	{
		$this->force_save_results = true;
	}
	protected function do_save_results()
	{
		return $this->file_name != null;
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
	public function get_run_description()
	{
		return $this->run_description;
	}
	public function result_already_contains_identifier()
	{
		$result_file = new pts_result_file($this->file_name);

		return in_array($this->results_identifier, $result_file->get_system_identifiers());
	}
	public function set_save_name($save_name)
	{
		if(empty($save_name))
		{
			$save_name = date("Y-m-d-Hi");
		}

		$this->file_name = self::clean_save_name_string($save_name);
		$this->file_name_title = $save_name;
	}
	public function set_results_identifier($identifier)
	{
		$this->results_identifier = $identifier;
	}
	public function prompt_save_name()
	{
		if($this->file_name != null)
		{
			return;
		}

		// Prompt to save a file when running a test
		$proposed_name = null;
		$custom_title = null;

		if(($env = pts_client::read_env("TEST_RESULTS_NAME")))
		{
			$custom_title = $enc;
			$proposed_name = self::clean_save_name_string($env);
			//echo "Saving Results To: " . $proposed_name . "\n";
		}

		if((pts_c::$test_flags ^ pts_c::batch_mode) || pts_config::read_bool_config(P_OPTION_BATCH_PROMPTSAVENAME, "FALSE"))
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

		if((pts_c::$test_flags ^ pts_c::batch_mode) || pts_config::read_bool_config(P_OPTION_BATCH_PROMPTIDENTIFIER, "TRUE") && (pts_c::$test_flags ^ pts_c::auto_mode) && (pts_c::$test_flags ^ pts_c::is_recovery))
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
				if($times_tried == 0 && (($env_identifier = pts_client::read_env("TEST_RESULTS_IDENTIFIER")) || (pts_c::$test_flags & pts_c::auto_mode)))
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
			while((!$no_repeated_tests && $identifier_pos != -1) || (isset($current_hardware[$identifier_pos]) && $current_hardware[$identifier_pos] != phodevi::system_hardware(true)) || (isset($current_software[$identifier_pos]) && $current_software[$identifier_pos] != phodevi::system_software(true)));
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
	public function result_file_setup()
	{
		$this->result_file_writer = new pts_result_file_writer($this->get_results_identifier());
	}
	public function call_test_runs()
	{
		// Create a lock
		$lock_path = pts_client::temporary_directory() . "/phoronix-test-suite.active";
		pts_client::create_lock($lock_path);

		if($this->pre_run_message != null)
		{
			pts_user_io::display_interrupt_message($this->pre_run_message);
		}

		// Hook into the module framework
		self::$test_run_process_active = true;
		pts_module_manager::module_process("__pre_run_process", $this);

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
					$test_flag = $this->process_test_run_request($i);
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
					$test_flag = $this->process_test_run_request($i, ($loop * $tests_to_run_count + $i + 1), ($total_loop_count * $tests_to_run_count));
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
				$test_flag = $this->process_test_run_request($i, ($i + 1), $tests_to_run_count);
			}
		}

		pts_file_io::unlink(SAVE_RESULTS_DIR . $this->get_file_name() . "/active.xml");

		foreach($this->tests_to_run as &$run_request)
		{
			// Remove cache shares
			foreach(pts_file_io::glob($run_request->test_profile->get_install_dir() . "cache-share-*.pt2so") as $cache_share_file)
			{
				unlink($cache_share_file);
			}
		}

		if($this->post_run_message != null)
		{
			pts_user_io::display_interrupt_message($this->post_run_message);
		}

		self::$test_run_process_active = -1;
		pts_module_manager::module_process("__post_run_process", $this);
		pts_client::release_lock($lock_path);

		// Report any tests that failed to properly run
		if((pts_c::$test_flags ^ pts_c::batch_mode) || (pts_c::$test_flags & pts_c::debug_mode) || $this->get_test_count() > 3)
		{
			if(count($this->failed_tests_to_run) > 0)
			{
				echo "\n\nThe following tests failed to properly run:\n\n";
				foreach($this->failed_tests_to_run as &$run_request)
				{
					echo "\t- " . $run_request->test_profile->get_identifier() . ($run_request->get_arguments_description() != null ? ": " . $run_request->get_arguments_description() : null) . "\n";
				}
				echo "\n";
			}
		}
	}
	public static function test_run_process_active()
	{
		return self::$test_run_process_active = true;
	}
	private function process_test_run_request($run_index, $run_position = -1, $run_count = -1)
	{
		$result = false;

		if($this->get_file_name() != null)
		{
			$this->result_file_writer->save_xml(SAVE_RESULTS_DIR . $this->get_file_name() . "/active.xml");
		}

		$test_run_request = $this->get_test_to_run($run_index);

		if(pts_is_test($test_run_request->test_profile->get_identifier()))
		{
			pts_set_assignment("TEST_RUN_POSITION", $run_position);
			pts_set_assignment("TEST_RUN_COUNT", $run_count);

			if(($run_position != 1 && count(pts_file_io::glob($test_run_request->test_profile->get_install_dir() . "cache-share-*.pt2so")) == 0))
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
					$this->result_file_writer->add_result_from_result_object($test_run_request, $test_run_request->get_result(), $test_run_request->test_result_buffer->get_values_as_string());
					$this->completed_runs += 1;

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
					pts_module_manager::module_process("__post_test_run_process", $this->result_file_writer);
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
	public static function clean_save_name_string($input)
	{
		$input = pts_strings::swap_variables($input, array("pts_client", "user_run_save_variables"));
		$input = trim(str_replace(array(' ', '/', '&', '?', ':', '$', '~', '\''), null, str_replace(" ", "-", strtolower($input))));

		return $input;
	}
	public static function initial_checks(&$to_run_identifiers, $test_flags = 0)
	{
		// Refresh the pts_client::$display in case we need to run in debug mode
		pts_client::init_display_mode($test_flags);

		if((pts_c::$test_flags & pts_c::batch_mode))
		{
			if(pts_config::read_bool_config(P_OPTION_BATCH_CONFIGURED, "FALSE") == false && (pts_c::$test_flags ^ pts_c::auto_mode))
			{
				pts_client::$display->generic_error("The batch mode must first be configured.\nTo configure, run phoronix-test-suite batch-setup");
				return false;
			}
		}

		if(!is_writable(TEST_ENV_DIR))
		{
			pts_client::$display->generic_error("The test installation directory is not writable.\nLocation: " . TEST_ENV_DIR);
			return false;
		}

		// Cleanup tests to run
		if(pts_test_run_manager::cleanup_tests_to_run($to_run_identifiers) == false)
		{
			return false;
		}
		else if(count($to_run_identifiers) == 0)
		{
			if(self::$user_rejected_install_notice == false)
			{
				pts_client::$display->generic_error("You must enter at least one test, suite, or result identifier to run.");
			}

			return false;
		}

		return true;
	}
	public function pre_execution_process()
	{
		if($this->do_save_results())
		{
			$test_properties = array();
			$this->result_file_setup();
			$this->results_directory = pts_client::setup_test_result_directory($this->get_file_name()) . '/';

			if((pts_c::$test_flags & pts_c::batch_mode))
			{
				pts_arrays::unique_push($test_properties, "PTS_BATCH_MODE");
			}
			else if((pts_c::$test_flags & pts_c::defaults_mode))
			{
				pts_arrays::unique_push($test_properties, "PTS_DEFAULTS_MODE");
			}

			if((pts_c::$test_flags ^ pts_c::is_recovering) && (!pts_is_test_result($this->get_file_name()) || $this->result_already_contains_identifier() == false))
			{
				$this->result_file_writer->add_result_file_meta_data($this, $test_properties);
				$this->result_file_writer->add_current_system_information();
				$this->wrote_system_xml = true;
			}
			else
			{
				$this->wrote_system_xml = false;
			}

			$pso = new pts_storage_object(true, false);
			$pso->add_object("test_run_manager", $this);
			$pso->add_object("batch_mode", (pts_c::$test_flags & pts_c::batch_mode));
			$pso->add_object("system_hardware", phodevi::system_hardware(false));
			$pso->add_object("system_software", phodevi::system_software(false));

			$pso->save_to_file($this->results_directory . "objects.pt2so");
			unset($pso);
		}
	}
	public function post_execution_process()
	{
		if($this->do_save_results())
		{
			if($this->completed_runs == 0 && !pts_is_test_result($this->get_file_name()) && (pts_c::$test_flags ^ pts_c::is_recovering) && (pts_c::$test_flags ^ pts_c::remote_mode))
			{
				pts_file_io::delete(SAVE_RESULTS_DIR . $this->get_file_name());
				return false;
			}

			pts_file_io::unlink($this->results_directory . "objects.pt2so");
			pts_file_io::delete(SAVE_RESULTS_DIR . $this->get_file_name() . "/test-logs/active/", null, true);

			if($this->wrote_system_xml)
			{
				$this->result_file_writer->add_test_notes(pts_test_notes_manager::generate_test_notes($test_type));
			}

			pts_module_manager::module_process("__event_results_process", $this);
			$this->result_file_writer->save_result_file($this->get_file_name());
			pts_module_manager::module_process("__event_results_saved", $this);
			//echo "\nResults Saved To: " . SAVE_RESULTS_DIR . $this->get_file_name() . "/composite.xml\n";
			pts_client::display_web_page(SAVE_RESULTS_DIR . $this->get_file_name() . "/index.html");

			if($this->allow_sharing_of_results && !defined("NO_NETWORK_COMMUNICATION"))
			{
				if($this->auto_upload_to_global)
				{
					$upload_results = true;
				}
				else
				{
					$upload_results = pts_user_io::prompt_bool_input("Would you like to upload these results to Phoronix Global", true, "UPLOAD_RESULTS");
				}

				if($upload_results)
				{
					$tags_input = pts_global::prompt_user_result_tags($to_run_identifiers);
					$upload_url = pts_global::upload_test_result(SAVE_RESULTS_DIR . $this->get_file_name() . "/composite.xml", $tags_input);

					if(!empty($upload_url))
					{
						echo "\nResults Uploaded To: " . $upload_url . "\n";
						pts_module_manager::module_process("__event_global_upload", $upload_url);
						pts_client::display_web_page($upload_url, "Do you want to launch Phoronix Global", true);
					}
					else
					{
						echo "\nResults Failed To Upload.\n";
					}
				}
			}
		}
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

			if((pts_c::$test_flags ^ pts_c::auto_mode) && (pts_c::$test_flags ^ pts_c::batch_mode))
			{
				$stop_and_install = pts_user_io::prompt_bool_input("Would you like to stop and install these tests now", true);

				if($stop_and_install)
				{
					pts_client::run_next("install_test", $tests_missing, pts_assignment_manager::get_all_assignments());
					self::$user_rejected_install_notice = false;
					return false;
				}
				else
				{
					self::$user_rejected_install_notice = true;
				}
			}
		}

		return true;
	}
	public function auto_save_results($save_name, $result_identifier, $description = null)
	{
		$this->set_save_name($save_name);
		$this->set_results_identifier($result_identifier);
		$this->run_description = $description;
	}
	public function save_results_prompt()
	{
		if($this->prompt_save_results && count($this->tests_to_run) > 0) // or check for DO_NOT_SAVE_RESULTS == false
		{
			if($this->force_save_results || pts_client::read_env("TEST_RESULTS_NAME"))
			{
				$save_results = true;
			}
			else
			{
				$save_results = pts_user_io::prompt_bool_input("Would you like to save these test results", true, "SAVE_RESULTS");
			}

			if($save_results)
			{
				// Prompt Save File Name
				$this->prompt_save_name();

				// Prompt Identifier
				$this->prompt_results_identifier();
				$unique_tests_r = array_unique($this->get_tests_to_run_identifiers());

				if(count($unique_tests_r) > 1 || $this->run_description == null)
				{
					$last = array_pop($unique_tests_r);
					array_push($unique_tests_r, "and " . $last);

					$this->run_description = "Running " . implode((count($unique_tests_r) > 2 ? ' ' : ", "), $unique_tests_r) . ".";
				}

				// Prompt Description
				if((pts_c::$test_flags ^ pts_c::auto_mode) && ((pts_c::$test_flags ^ pts_c::batch_mode) || pts_config::read_bool_config(P_OPTION_BATCH_PROMPTDESCRIPTION, "FALSE")))
				{
					if($this->run_description == null)
					{
						$this->run_description = "N/A";
					}

					pts_client::$display->generic_heading("If you wish, enter a new description below.\nPress ENTER to proceed without changes.");
					echo "Current Description: " . $this->run_description . "\n\nNew Description: ";
					$new_test_description = pts_user_io::read_user_input();

					if(!empty($new_test_description))
					{
						$this->run_description = $new_test_description;
					}
				}
			}
		}
	}
	public function load_tests_to_run($to_run_identifiers)
	{
		// Determine what to run
		$this->determine_tests_to_run($to_run_identifiers);

		// Run the test process
		$this->validate_tests_to_run();

		// Is there something to run?
		return $this->get_test_count() > 0;
	}
	public function load_result_file_to_run($save_name, $result_identifier, &$result_file, $tests_to_complete = null)
	{
		// Determine what to run
		$this->auto_save_results($save_name, $result_identifier);
		$this->run_description = $result_file->get_suite_description();
		$result_objects = $result_file->get_result_objects();

		// Unset result objects that shouldn't be run
		if(is_array($tests_to_complete))
		{
			foreach(array_keys($result_objects) as $i)
			{
				if(!in_array($i, $tests_to_complete))
				{
					unset($result_objects[$i]);
				}
			}
		}

		if(count($result_objects) == 0)
		{
			return false;
		}

		$test_run = array();
		$test_args = array();
		$test_args_description = array();

		foreach($result_objects as &$result_object)
		{
			array_push($test_run, $result_object->test_profile->get_identifier());
			array_push($test_args, $result_object->get_arguments());
			array_push($test_args_description, $result_object->get_arguments_description());
		}

		$this->add_multi_test_run($test_run, $test_args, $test_args_description);
	
		// Run the test process
		$this->validate_tests_to_run();

		// Is there something to run?
		return $this->get_test_count() > 0;
	}
	public function load_test_run_requests_to_run($save_name, $result_identifier, &$result_file, &$test_run_requests)
	{
		// Determine what to run
		$this->auto_save_results($save_name, $result_identifier);
		$this->run_description = $result_file->get_suite_description();

		if(count($test_run_requests) == 0)
		{
			return false;
		}

		$test_run = array();
		$test_args = array();
		$test_args_description = array();
		$test_override_options = array();

		foreach($test_run_requests as &$test_run_request)
		{
			array_push($test_run, $test_run_request->test_profile->get_identifier());
			array_push($test_args, $test_run_request->get_arguments());
			array_push($test_args_description, $test_run_request->get_arguments_description());
			array_push($test_override_options, $test_run_request->test_profile->get_override_values());
		}

		$this->add_multi_test_run($test_run, $test_args, $test_args_description, $test_override_options);
	
		// Run the test process
		$this->validate_tests_to_run();

		// Is there something to run?
		return $this->get_test_count() > 0;
	}
	public function determine_tests_to_run($to_run_identifiers)
	{
		$unique_test_count = count(array_unique($to_run_identifiers));
		$run_contains_a_no_result_type = false;
		$request_results_save = false;

		foreach($to_run_identifiers as $to_run)
		{
			$to_run = strtolower($to_run);

			if(!pts_is_test_result($to_run) && pts_global::is_global_id($to_run))
			{
				pts_global::clone_global_result($to_run);
			}

			if(pts_is_test($to_run))
			{
				if($run_contains_a_no_result_type == false)
				{
					$test_profile = new pts_test_profile($to_run);

					if($test_profile->get_result_format() == "NO_RESULT")
					{
						$run_contains_a_no_result_type = true;
					}
					if($test_profile->do_auto_save_results())
					{
						$request_results_save = true;
					}
				}

				if((pts_c::$test_flags & pts_c::batch_mode) && pts_config::read_bool_config(P_OPTION_BATCH_TESTALLOPTIONS, "TRUE"))
				{
					list($test_arguments, $test_arguments_description) = pts_test_run_options::batch_user_options($to_run);
				}
				else if((pts_c::$test_flags & pts_c::defaults_mode))
				{
					list($test_arguments, $test_arguments_description) = pts_test_run_options::default_user_options($to_run);
				}
				else
				{
					list($test_arguments, $test_arguments_description) = pts_test_run_options::prompt_user_options($to_run);
				}

				$this->add_single_test_run($to_run, $test_arguments, $test_arguments_description);
			}
			else if(pts_is_suite($to_run))
			{
				// Print the $to_run ?
				$test_suite = new pts_test_suite($to_run);

				$this->pre_run_message = $test_suite->get_pre_run_message();
				$this->post_run_message = $test_suite->get_post_run_message();
				$suite_run_mode = $test_suite->get_run_mode();

				if($suite_run_mode == "PCQS")
				{
					$this->is_pcqs = true;
				}

				$this->add_suite_run($to_run);
			}
			else if(pts_is_test_result($to_run))
			{
				// Print the $to_run ?
				$result_file = new pts_result_file($to_run);
				$this->run_description = $result_file->get_suite_description();
				$test_extensions = $result_file->get_suite_extensions();
				$test_previous_properties = $result_file->get_suite_properties();
				$result_objects = $result_file->get_result_objects();
				$test_override_options = array();

				$this->set_save_name($to_run);

				foreach(explode(";", $test_previous_properties) as $test_prop)
				{
					// TODO: hook into PTS3 arch
					//pts_arrays::unique_push($test_properties, $test_prop);
				}

				pts_module_manager::process_extensions_string($test_extensions);

				$test_run = array();
				$test_args = array();
				$test_args_description = array();

				foreach($result_objects as &$result_object)
				{
					array_push($test_run, $result_object->test_profile->get_identifier());
					array_push($test_args, $result_object->get_arguments());
					array_push($test_args_description, $result_object->get_arguments_description());
				}

				$this->add_multi_test_run($test_run, $test_args, $test_args_description, $test_override_options);
			}
			else
			{
				pts_client::$display->generic_error($to_run . " is not recognized.");
				continue;
			}
		}

		$this->prompt_save_results = $run_contains_a_no_result_type == false || $unique_test_count > 1;
		$this->force_save_results = $this->force_save_results || $request_results_save;
	}
	public function validate_tests_to_run()
	{
		$failed_tests = array();
		$validated_run_requests = array();
		$allow_results_sharing = true;
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

			if(!is_dir($test_run_request->test_profile->get_install_dir()))
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

			if($test_run_request->test_profile->is_root_required() && (pts_c::$test_flags & pts_c::batch_mode) && phodevi::read_property("system", "username") != "root")
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

			if($allow_results_sharing && $test_run_request->test_profile->allow_results_sharing() == false)
			{
				// One of the contained test profiles does not allow Global uploads, so block it
				$allow_results_sharing = false;
			}

			array_push($validated_run_requests, $test_run_request);
		}

		if($allow_results_sharing == false)
		{
			$this->allow_sharing_of_results = false;
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
	public static function standard_run($to_run, $test_flags = 0)
	{
		if(pts_test_run_manager::initial_checks($to_run, $test_flags) == false)
		{
			return false;
		}

		$test_run_manager = new pts_test_run_manager($test_flags);

		// Load the tests to run
		if($test_run_manager->load_tests_to_run($to_run) == false)
		{
			return false;
		}

		// Save results?
		$test_run_manager->save_results_prompt();

		// Run the actual tests
		$test_run_manager->pre_execution_process();
		$test_run_manager->call_test_runs();
		$test_run_manager->post_execution_process();
		echo "\n";
	}
}

?>
