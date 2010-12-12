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
	private $test_run_pos = 0;
	private $test_run_count = 0;

	private $file_name = null;
	private $file_name_title = null;
	private $results_identifier = null;
	private $run_description = null;

	private $force_save_results = false;
	private $prompt_save_results = true;
	private $post_run_message = null;
	private $pre_run_message = null;
	private $allow_sharing_of_results = true;
	private $auto_upload_to_openbenchmarking = false;
	private $is_pcqs = false;

	private $do_dynamic_run_count = false;
	private $dynamic_roun_count_on_length_or_less;
	private $dynamic_run_count_std_deviation_threshold;
	private $dynamic_run_count_export_script;

	// These variables could potentially be cleaned up or eliminated
	private $results_directory;
	private $wrote_system_xml;

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
	public function auto_upload_to_openbenchmarking($do = true)
	{
		$this->auto_upload_to_openbenchmarking = ($do == true);
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
		if(($ex_file = $this->dynamic_run_count_export_script) != null && is_executable($ex_file) || is_executable(($ex_file = PTS_USER_PATH . $this->dynamic_run_count_export_script)))
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
	protected function add_test_result_object(&$test_result)
	{
		if($this->validate_test_to_run($test_result->test_profile))
		{
			pts_arrays::unique_push($this->tests_to_run, $test_result);
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
				$estimated_time += $this->tests_to_run[$i]->test_profile->get_estimated_run_time();
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
	public function get_title()
	{
		return $this->file_name_title;
	}
	public function get_results_identifier()
	{
		return $this->results_identifier;
	}
	public function get_description()
	{
		return $this->run_description;
	}
	public function get_notes()
	{
		return null; // TODO: Not Yet Implemented
	}
	public function get_internal_tags()
	{
		return null;
	}
	public function get_reference_id()
	{
		return null;
	}
	public function get_preset_environment_variables()
	{
		return pts_module_manager::var_store_string();
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
		$this->force_save_results = true;
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

			while(empty($proposed_name) || ($is_reserved_word = pts_types::is_test_or_suite($proposed_name)))
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

		if(pts_result_file::is_test_result_file($this->file_name))
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

		if((pts_c::$test_flags ^ pts_c::batch_mode) || pts_config::read_bool_config(P_OPTION_BATCH_PROMPTIDENTIFIER, "TRUE") && (pts_c::$test_flags ^ pts_c::auto_mode) && (pts_c::$test_flags ^ pts_c::is_recovering))
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
	public function get_test_run_position()
	{
		return $this->test_run_pos + 1;
	}
	public function get_test_run_count_reported()
	{
		return $this->test_run_count;
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
		pts_client::process_user_config_external_hook_process(P_OPTION_EXTERNAL_HOOKS_PRE_TESTING, "Running the pre-test external hook.");

		pts_file_io::unlink(PTS_USER_PATH . "halt-testing");
		pts_file_io::unlink(PTS_USER_PATH . "skip-test");

		$test_flag = true;
		$tests_to_run_count = $this->get_test_count();
		pts_client::$display->test_run_process_start($this);

		if(($total_loop_time_minutes = pts_client::read_env("TOTAL_LOOP_TIME")) && is_numeric($total_loop_time_minutes) && $total_loop_time_minutes > 0)
		{
			$total_loop_time_seconds = $total_loop_time_minutes * 60;
			$loop_end_time = time() + $total_loop_time_seconds;

			pts_client::$display->generic_heading("Estimated Run-Time: " . pts_strings::format_time($total_loop_time_seconds, "SECONDS", true, 60));
			$this->test_run_count = $tests_to_run_count;

			do
			{
				for($i = 0; $i < $tests_to_run_count && $test_flag && time() < $loop_end_time; $i++)
				{
					$this->test_run_pos = $i;
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

			$this->test_run_count = ($total_loop_count * $tests_to_run_count);

			for($loop = 0; $loop < $total_loop_count && $test_flag; $loop++)
			{
				for($i = 0; $i < $tests_to_run_count && $test_flag; $i++)
				{
					$this->test_run_pos = ($loop * $tests_to_run_count + $i);
					$test_flag = $this->process_test_run_request($i);
				}
			}
		}
		else
		{
			if(($estimated_length = $this->get_estimated_run_time()) > 1)
			{
				pts_client::$display->generic_heading("Estimated Run-Time: " . pts_strings::format_time($estimated_length, "SECONDS", true, 60));
			}

			$this->test_run_count = $tests_to_run_count;

			for($i = 0; $i < $tests_to_run_count && $test_flag; $i++)
			{
				$this->test_run_pos = $i;
				$test_flag = $this->process_test_run_request($i);
			}
		}

		pts_file_io::unlink(PTS_SAVE_RESULTS_PATH . $this->get_file_name() . "/active.xml");

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
		pts_client::process_user_config_external_hook_process(P_OPTION_EXTERNAL_HOOKS_POST_TESTING, "Running the post-test external hook.");
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
	private function process_test_run_request($run_index)
	{
		$result = false;

		if($this->get_file_name() != null)
		{
			$this->result_file_writer->save_xml(PTS_SAVE_RESULTS_PATH . $this->get_file_name() . "/active.xml");
		}

		$test_run_request = $this->get_test_to_run($run_index);

		if(($run_index != 0 && count(pts_file_io::glob($test_run_request->test_profile->get_install_dir() . "cache-share-*.pt2so")) == 0))
		{
			sleep(pts_config::read_user_config(P_OPTION_TEST_SLEEPTIME, 5));
		}

		pts_test_execution::run_test($this, $test_run_request);

		if(pts_file_io::unlink(PTS_USER_PATH . "halt-testing"))
		{
			// Stop the testing process entirely
			return false;
		}
		else if(pts_file_io::unlink(PTS_USER_PATH . "skip-test"))
		{
			// Just skip the current test and do not save the results, but continue testing
			continue;
		}

		$test_successful = false;
		if($test_run_request->test_profile->get_display_format() == "NO_RESULT")
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
					$this->result_file_writer->add_result_from_result_object_with_value_string($test_run_request, $test_run_request->get_result(), $test_run_request->test_result_buffer->get_values_as_string());

					if($this->get_results_identifier() != null && $this->get_file_name() != null && pts_config::read_bool_config(P_OPTION_LOG_TEST_OUTPUT, "FALSE"))
					{
						static $xml_write_pos = 1;
						pts_file_io::mkdir(PTS_SAVE_RESULTS_PATH . $this->get_file_name() . "/test-logs/" . $xml_write_pos . "/");

						if(is_dir(PTS_SAVE_RESULTS_PATH . $this->get_file_name() . "/test-logs/active/" . $this->get_results_identifier()))
						{
							$test_log_write_dir = PTS_SAVE_RESULTS_PATH . $this->get_file_name() . "/test-logs/" . $xml_write_pos . '/' . $this->get_results_identifier() . '/';

							if(is_dir($test_log_write_dir))
							{
								pts_file_io::delete($test_log_write_dir, null, true);
							}

							rename(PTS_SAVE_RESULTS_PATH . $this->get_file_name() . "/test-logs/active/" . $this->get_results_identifier() . '/', $test_log_write_dir);
						}
						$xml_write_pos++;
					}
				}
			}

			pts_file_io::unlink(PTS_SAVE_RESULTS_PATH . $this->get_file_name() . "/test-logs/active/");
		}

		if($test_successful == false && $test_run_request->test_profile->get_identifier() != null)
		{
			array_push($this->failed_tests_to_run, $test_run_request);

			// For now delete the failed test log files, but it may be a good idea to keep them
			pts_file_io::delete(PTS_SAVE_RESULTS_PATH . $this->get_file_name() . "/test-logs/active/" . $this->get_results_identifier() . "/", null, true);
		}

		pts_module_manager::module_process("__post_test_run_process", $this->result_file_writer);
		pts_client::process_user_config_external_hook_process(P_OPTION_EXTERNAL_HOOKS_INTERIM_TESTING, "Running the interim-test external hook.", $test_run_request);

		return true;
	}
	public static function clean_save_name_string($input)
	{
		$input = pts_strings::swap_variables($input, array("pts_client", "user_run_save_variables"));
		$input = trim(str_replace(array(' ', '/', '&', '?', ':', '$', '~', '\''), null, str_replace(" ", "-", strtolower($input))));

		return $input;
	}
	public static function initial_checks(&$to_run, $test_flags = 0)
	{
		// Refresh the pts_client::$display in case we need to run in debug mode
		pts_client::init_display_mode($test_flags);
		pts_client::set_test_flags($test_flags);
		$to_run = pts_types::identifiers_to_objects($to_run);

		if((pts_c::$test_flags & pts_c::batch_mode))
		{
			if(pts_config::read_bool_config(P_OPTION_BATCH_CONFIGURED, "FALSE") == false && (pts_c::$test_flags ^ pts_c::auto_mode))
			{
				pts_client::$display->generic_error("The batch mode must first be configured.\nTo configure, run phoronix-test-suite batch-setup");
				return false;
			}
		}

		if(!is_writable(PTS_TEST_INSTALL_PATH))
		{
			pts_client::$display->generic_error("The test installation directory is not writable.\nLocation: " . PTS_TEST_INSTALL_PATH);
			return false;
		}

		// Cleanup tests to run
		if(pts_test_run_manager::cleanup_tests_to_run($to_run) == false)
		{
			return false;
		}
		else if(count($to_run) == 0)
		{
			pts_client::$display->generic_error("You must enter at least one test, suite, or result identifier to run.");

			return false;
		}

		return true;
	}
	public function pre_execution_process()
	{
		if($this->do_save_results())
		{
			$this->result_file_setup();
			$this->results_directory = pts_client::setup_test_result_directory($this->get_file_name()) . '/';

			if((pts_c::$test_flags ^ pts_c::is_recovering) && (!pts_result_file::is_test_result_file($this->get_file_name()) || $this->result_already_contains_identifier() == false))
			{
				$this->result_file_writer->add_result_file_meta_data($this);
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
			if($this->result_file_writer->get_result_count() == 0 && !pts_result_file::is_test_result_file($this->get_file_name()) && (pts_c::$test_flags ^ pts_c::is_recovering) && (pts_c::$test_flags ^ pts_c::remote_mode))
			{
				pts_file_io::delete(PTS_SAVE_RESULTS_PATH . $this->get_file_name());
				return false;
			}

			pts_file_io::unlink($this->results_directory . "objects.pt2so");
			pts_file_io::delete(PTS_SAVE_RESULTS_PATH . $this->get_file_name() . "/test-logs/active/", null, true);

			if($this->wrote_system_xml)
			{
				$this->result_file_writer->add_test_notes(pts_test_notes_manager::generate_test_notes($this->tests_to_run));
			}

			pts_module_manager::module_process("__event_results_process", $this);
			$this->result_file_writer->save_result_file($this->get_file_name());
			pts_module_manager::module_process("__event_results_saved", $this);
			//echo "\nResults Saved To: " . PTS_SAVE_RESULTS_PATH . $this->get_file_name() . "/composite.xml\n";
			pts_client::display_web_page(PTS_SAVE_RESULTS_PATH . $this->get_file_name() . "/index.html");

			if($this->allow_sharing_of_results && !defined("NO_NETWORK_COMMUNICATION"))
			{
				if($this->auto_upload_to_openbenchmarking)
				{
					$upload_results = true;
				}
				else
				{
					$upload_results = pts_user_io::prompt_bool_input("Would you like to upload these results to OpenBenchmarking.org", true, "UPLOAD_RESULTS");
				}

				if($upload_results)
				{
					$upload_url = pts_openbenchmarking::upload_test_result($this);

					if(!empty($upload_url))
					{
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
	public static function cleanup_tests_to_run(&$to_run_objects)
	{
		$skip_tests = ($e = pts_client::read_env("SKIP_TESTS")) ? pts_strings::comma_explode($e) : false;
		$tests_verified = array();
		$tests_missing = array();

		foreach($to_run_objects as &$run_object)
		{
			if($skip_tests && in_array($run_object->get_identifier(false), $skip_tests))
			{
				echo "Skipping: " . $run_object->get_identifier() . "\n";
				continue;
			}
			else if($run_object instanceof pts_test_profile)
			{
				if($run_object->get_title() == null)
				{
					echo "Not A Test: " . $run_object . "\n";
					continue;
				}
				else
				{
					if($run_object->is_supported() == false)
					{
						continue;
					}

					if($run_object->is_test_installed() == false)
					{
						array_push($tests_missing, $run_object);
						continue;
					}
				}
			}
			else if($run_object instanceof pts_result_file)
			{
				$num_installed = 0;
				foreach($run_object->get_contained_test_profiles() as $test_profile)
				{
					if($test_profile->is_test_installed() == false)
					{
						array_push($tests_missing, $test_profile);
					}
					else
					{
						$num_installed++;
					}
				}

				if($num_installed == 0)
				{
					continue;
				}
			}
			else if($run_object instanceof pts_test_suite || $run_object instanceof pts_virtual_test_suite)
			{
				if($run_object->is_core_version_supported() == false)
				{
					echo $run_object->get_title() . " is a suite not supported by this version of the Phoronix Test Suite.\n";
					continue;
				}

				$num_installed = 0;

				foreach($run_object->get_contained_test_profiles() as $test_profile)
				{
					if($test_profile->is_test_installed() == false)
					{
						array_push($tests_missing, $run_object);
					}
					else
					{
						$num_installed++;
					}
				}

				if($num_installed == 0)
				{
					continue;
				}
			}
			else
			{
				echo "Not Recognized: " . $run_object . "\n";
				continue;
			}

			array_push($tests_verified, $run_object);
		}

		$to_run_objects = $tests_verified;

		if(count($tests_missing) > 0)
		{
			$tests_missing = array_unique($tests_missing);

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
					pts_test_installer::standard_install($tests_missing, pts_c::$test_flags);
					self::cleanup_tests_to_run($tests_missing);
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
		if(($this->prompt_save_results || $this->force_save_results) && count($this->tests_to_run) > 0) // or check for DO_NOT_SAVE_RESULTS == false
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
					$this->run_description = "Running " . implode(", ", $unique_tests_r) . ".";
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
	public function load_tests_to_run(&$to_run_objects)
	{
		// Determine what to run
		$this->determine_tests_to_run($to_run_objects);

		// Is there something to run?
		return $this->get_test_count() > 0;
	}
	public function load_result_file_to_run($save_name, $result_identifier, &$result_file, $tests_to_complete = null)
	{
		// Determine what to run
		$this->auto_save_results($save_name, $result_identifier);
		$this->run_description = $result_file->get_description();
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


		foreach($result_objects as &$result_object)
		{
			if($this->validate_test_to_run($result_object->test_profile))
			{
				$test_result = new pts_test_result($result_object->test_profile);
				$test_result->set_used_arguments($result_object->get_arguments());
				$test_result->set_used_arguments_description($result_object->get_arguments_description());
				$this->add_test_result_object($test_result);
			}
		}

		// Is there something to run?
		return $this->get_test_count() > 0;
	}
	public function load_test_run_requests_to_run($save_name, $result_identifier, &$result_file, &$test_run_requests)
	{
		// Determine what to run
		$this->auto_save_results($save_name, $result_identifier);
		$this->run_description = $result_file->get_description();

		if(count($test_run_requests) == 0)
		{
			return false;
		}

		foreach($test_run_requests as &$test_run_request)
		{
			if($this->validate_test_to_run($test_run_request->test_profile))
			{
				continue;
			}

			if($test_run_request->test_profile->get_override_values() != null)
			{
				$test_run_request->test_profile->set_override_values($test_run_request->test_profile->get_override_values());
			}

			$test_result = new pts_test_result($test_run_request->test_profile);
			$test_result->set_used_arguments($test_run_request->get_arguments());
			$test_result->set_used_arguments_description($test_run_request->get_arguments_description());
			$this->add_test_result_object($test_result);
		}

		// Is there something to run?
		return $this->get_test_count() > 0;
	}
	protected function test_prompts_to_result_objects(&$test_profile)
	{
		$result_objects = array();

		if((pts_c::$test_flags & pts_c::batch_mode) && pts_config::read_bool_config(P_OPTION_BATCH_TESTALLOPTIONS, "TRUE"))
		{
			list($test_arguments, $test_arguments_description) = pts_test_run_options::batch_user_options($test_profile);
		}
		else if((pts_c::$test_flags & pts_c::defaults_mode))
		{
			list($test_arguments, $test_arguments_description) = pts_test_run_options::default_user_options($test_profile);
		}
		else
		{
			list($test_arguments, $test_arguments_description) = pts_test_run_options::prompt_user_options($test_profile);
		}

		foreach(array_keys($test_arguments) as $i)
		{
			$test_result = new pts_test_result($test_profile);
			$test_result->set_used_arguments($test_arguments[$i]);
			$test_result->set_used_arguments_description($test_arguments_description[$i]);
			array_push($result_objects, $test_result);
		}

		return $result_objects;
	}
	public function determine_tests_to_run(&$to_run_objects)
	{
		$unique_test_count = count(array_unique($to_run_objects));
		$run_contains_a_no_result_type = false;
		$request_results_save = false;

		foreach($to_run_objects as &$run_object)
		{
			// TODO: determine whether to print the titles of what's being run?
			if($run_object instanceof pts_test_profile)
			{
				if($run_object->get_title() == null || $this->validate_test_to_run($run_object))
				{
					continue;
				}

				if($run_contains_a_no_result_type == false && $run_object->get_display_format() == "NO_RESULT")
				{
					$run_contains_a_no_result_type = true;
				}
				if($request_results_save == false && $run_object->do_auto_save_results())
				{
					$request_results_save = true;
				}

				foreach(self::test_prompts_to_result_objects($run_object) as $result_object)
				{
					$this->add_test_result_object($result_object);
				}
			}
			else if($run_object instanceof pts_test_suite)
			{
				$this->pre_run_message = $run_object->get_pre_run_message();
				$this->post_run_message = $run_object->get_post_run_message();

				if($run_object->get_run_mode() == "PCQS")
				{
					$this->is_pcqs = true;
				}

				foreach($run_object->get_contained_test_result_objects() as $result_object)
				{
					$this->add_test_result_object($result_object);
				}
			}
			else if($run_object instanceof pts_result_file)
			{
				// Print the $to_run ?
				$this->run_description = $run_object->get_description();
				$preset_vars = $run_object->get_preset_environment_variables();
				$result_objects = $run_object->get_result_objects();

				$this->set_save_name($run_object->get_identifier());

				pts_module_manager::process_environment_variables_string_to_set($preset_vars);

				foreach($result_objects as &$result_object)
				{
					$test_result = new pts_test_result($result_object->test_profile);
					$test_result->set_used_arguments($result_object->get_arguments());
					$test_result->set_used_arguments_description($result_object->get_arguments_description());
					$this->add_test_result_object($test_result);
				}
			}
			else if($run_object instanceof pts_virtual_test_suite)
			{
				foreach($run_object->get_contained_test_profiles() as $test_profile)
				{
					// The user is to configure virtual suites manually
					foreach(self::test_prompts_to_result_objects($test_profile) as $result_object)
					{
						$this->add_test_result_object($result_object);
					}
				}
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
	protected function validate_test_to_run(&$test_profile)
	{
		static $test_checks = null;

		if(!isset($test_checks[$test_profile]))
		{
			$valid_test_profile = true;
			$allow_results_sharing = $this->allow_sharing_of_results;
			$display_driver = phodevi::read_property('system', 'display-driver');
			$skip_tests = pts_client::read_env('SKIP_TESTS');

			// Validate the empty pts_test_result
			$test_type = $test_profile->get_test_hardware_type();

			if($test_type == 'Graphics' && pts_client::read_env('DISPLAY') == false && IS_WINDOWS == false)
			{
				pts_client::$display->test_run_error("No display server was found, cannot run " . $test_profile);
				$valid_test_profile = false;
			}
			else if($test_type == 'Graphics' && in_array($display_driver, array('vesa', 'nv', 'cirrus')))
			{
				pts_client::$display->test_run_error("A display driver without 3D acceleration was found, cannot run " . $test_profile);
				$valid_test_profile = false;
			}
			else if(pts_client::read_env('NO_' . strtoupper($test_type) . '_TESTS') || ($skip_tests && in_array($test_profile, pts_strings::comma_explode($skip_tests))) || ($skip_tests && in_array($test_type, pts_strings::comma_explode($skip_tests))))
			{
				pts_client::$display->test_run_error("Due to a pre-set environmental variable, skipping " . $test_profile);
				$valid_test_profile = false;
			}
			else if($test_profile->is_root_required() && (pts_c::$test_flags & pts_c::batch_mode) && phodevi::read_property("system", "username") != "root")
			{
				pts_client::$display->test_run_error("Cannot run " . $test_profile . " in batch mode as root access is required.");
				$valid_test_profile = false;
			}
			else if($test_profile->get_test_executable_dir() == null)
			{
				pts_client::$display->test_run_error("The test executable for " . $test_profile . " could not be located.");
				$valid_test_profile = false;
			}

			if($valid_test_profile && $this->allow_sharing_of_results && $test_profile->allow_results_sharing() == false)
			{
				$this->allow_sharing_of_results = false;
			}

			$test_checks[$test_profile] = $valid_test_profile;
		}

		return $test_checks[$test_profile];
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
