<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-includes-run.php: Functions needed for running tests/suites.

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

function pts_prompt_results_identifier($current_identifiers = null)
{
	// Prompt for a results identifier
	$results_identifier = null;
	$show_identifiers = array();

	if(pts_read_assignment("IS_BATCH_MODE") == false || pts_batch_prompt_test_identifier())
	{
		if(is_array($current_identifiers) && count($current_identifiers) > 0)
		{
			foreach($current_identifiers as $identifier)
			{
				if(is_array($identifier))
				{
					foreach($identifier as $identifier_2)
					{
						array_push($show_identifiers, $identifier_2);
					}
				}
				else
				{
					array_push($show_identifiers, $identifier);
				}
			}

			$show_identifiers = array_unique($show_identifiers);

			echo "\nCurrent Test Identifiers:\n";
			foreach($show_identifiers as $identifier)
			{
				echo "- " . $identifier . "\n";
			}
			echo "\n";
		}

		$times_tried = 0;
		do
		{
			if($times_tried == 0 && (($env_identifier = getenv("TEST_RESULTS_IDENTIFIER")) != false || 
			($env_identifier = pts_read_assignment("AUTO_TEST_RESULTS_IDENTIFIER")) != false))
			{
				$results_identifier = $env_identifier;
				echo "Test Identifier: " . $results_identifier . "\n";
			}
			else
			{
				echo "Enter a unique name for this test run: ";
				$results_identifier = trim(str_replace(array("/"), "", fgets(STDIN)));
			}
			$times_tried++;
		}
		while(empty($results_identifier) || in_array($results_identifier, $show_identifiers));
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

	return $results_identifier;
}
function pts_prompt_svg_result_options($svg_file)
{
	// Image graph result driven test selection
	$svg_parser = new tandem_XmlReader($svg_file);
	$svg_test = array_pop($svg_parser->getStatement("Test"));
	$svg_identifier = array_pop($svg_parser->getStatement("Identifier"));
	$test_to_run = null;

	if(!empty($svg_test) && !empty($svg_identifier))
	{
		$run_options = array();
		if(pts_is_test($svg_test))
		{
			array_push($run_options, array($svg_test, "Run this test (" . $svg_test . ")"));
		}
		if(pts_is_suite($svg_identifier))
		{
			array_push($run_options, array($svg_identifier, "Run this suite (" . $svg_identifier . ")"));
		}
		else if(pts_is_global_id($svg_identifier))
		{
			array_push($run_options, array($svg_identifier, "Run this Phoronix Global comparison (" . $svg_identifier . ")"));
		}

		$run_option_count = count($run_options);
		if($run_option_count > 0)
		{
			if($run_option_count == 1)
			{
				$test_to_run = $run_options[0][0];
			}
			else
			{
				do
				{
					echo "\n";
					for($i = 0; $i < $run_option_count; $i++)
					{
						echo ($i + 1) . ": " . $run_options[$i][1] . "\n";
					}
					echo "\nEnter Your Choice: ";

					$run_choice = trim(fgets(STDIN));
				}
				while($run_choice < 1 || $run_choice > $run_option_count);
				$test_to_run = $run_options[($run_choice - 1)][0];
			}
		}
	}

	return $test_to_run;
}
function pts_prompt_test_options($identifier)
{
	$xml_parser = new pts_test_tandem_XmlReader($identifier);
	$test_title = $xml_parser->getXMLValue(P_TEST_TITLE);

	$user_args = "";
	$text_args = "";
	$test_options = pts_test_options($identifier);

	if(count($test_options) > 0)
	{
		echo pts_string_header("Test Configuration: " . $test_title);
	}

	for($this_option_pos = 0; $this_option_pos < count($test_options); $this_option_pos++)
	{
		$o = $test_options[$this_option_pos];
		$option_count = $o->option_count();

		if($option_count == 0)
		{
			// User inputs their option
			do
			{
				echo "\n" . $o->get_name() . "\n" . "Enter Value: ";
				$value = strtolower(trim(fgets(STDIN)));
			}
			while(empty($value));

			$user_args .= $o->get_option_prefix() . $value . $o->get_option_postfix();
		}
		else
		{
			if($option_count == 1)
			{
				// Only one option in menu, so auto-select it
				$bench_choice = 1;
			}
			else
			{
				// Have the user select the desired option
				echo "\n" . $o->get_name() . ":\n";
				$all_option_names = $o->get_all_option_names();

				do
				{
					echo "\n";
					for($i = 0; $i < $option_count; $i++)
					{
						echo ($i + 1) . ": " . $o->get_option_name($i) . "\n";
					}
					echo "\nEnter Your Choice: ";
					$bench_choice = trim(fgets(STDIN));
				}
				while(($bench_choice < 1 || $bench_choice > $option_count) && !in_array($bench_choice, $all_option_names));

				if(!is_numeric($bench_choice) && in_array($bench_choice, $all_option_names))
				{
					$match_made = false;

					for($i = 0; $i < $option_count && !$match_made; $i++)
					{
						if($o->get_option_name($i) == $bench_choice)
						{
							$bench_choice = ($i + 1);
							$match_made = true;
						}
					}
				}
			}

			// Format the selected option
			$option_display_name = $o->get_option_name(($bench_choice - 1));

			if(($cut_point = strpos($option_display_name, "(")) > 1 && strpos($option_display_name, ")") > $cut_point)
			{
				$option_display_name = substr($option_display_name, 0, $cut_point);
			}

			if(count($test_options) > 1)
			{
				$text_args .= $o->get_name() . ": ";
			}
			$text_args .= $option_display_name;

			if($this_option_pos < (count($test_options) - 1))
			{
				$text_args .= " - ";
			}

			$user_args .= $o->get_option_prefix() . $o->get_option_value(($bench_choice - 1)) . $o->get_option_postfix() . " ";
		}
	}

	return array($user_args, $text_args);
}
function pts_defaults_test_options($identifier)
{
	// Defaults mode for single test
	$all_args_real = array();
	$all_args_description = array();
	$description_separate = " - ";
	$test_options = pts_test_options($identifier);

	for($this_option_pos = 0; $this_option_pos < count($test_options); $this_option_pos++)
	{
		$o = $test_options[$this_option_pos];
		$option_count = $o->option_count();

		$option_args = array();
		$option_args_description = array();

		$default_entry = $o->get_option_default();

		if($option_count == 2)
		{
			for($i = 0; $i < $option_count; $i++)
			{
				$this_arg = $o->get_option_prefix() . $o->get_option_value($i) . $o->get_option_postfix();
				$this_arg_description = $o->get_name() . ": " . $o->get_option_name($i);

				if(($cut_point = strpos($this_arg_description, "(")) > 1 && strpos($this_arg_description, ")") > $cut_point)
				{
					$this_arg_description = substr($this_arg_description, 0, $cut_point);
				}

				array_push($option_args, $this_arg);
				array_push($option_args_description, $this_arg_description);
			}
		}
		else
		{
			$this_arg = $o->get_option_prefix() . $o->get_option_value($default_entry) . $o->get_option_postfix();
			$this_arg_description = $o->get_name() . ": " . $o->get_option_name($default_entry);

			if(($cut_point = strpos($this_arg_description, "(")) > 1 && strpos($this_arg_description, ")") > $cut_point)
			{
				$this_arg_description = substr($this_arg_description, 0, $cut_point);
			}
			array_push($option_args, $this_arg);
			array_push($option_args_description, $this_arg_description);
		}

		array_push($all_args_real, $option_args);
		array_push($all_args_description, $option_args_description);
	}

	$test_args = array();
	pts_all_combos($test_args, "", $all_args_real, 0);

	$test_args_description = array();
	pts_all_combos($test_args_description, "", $all_args_description, 0, $description_separate);

	return array($test_args, $test_args_description);
}
function pts_generate_batch_run_options($identifier)
{
	// Batch mode for single test
	$batch_all_args_real = array();
	$batch_all_args_description = array();
	$description_separate = " ";
	$test_options = pts_test_options($identifier);

	for($this_option_pos = 0; $this_option_pos < count($test_options); $this_option_pos++)
	{
		$o = $test_options[$this_option_pos];
		$option_count = $o->option_count();

		$option_args = array();
		$option_args_description = array();

		for($i = 0; $i < $option_count; $i++)
		{
			// A bit redundant processing, but will ensure against malformed XML problems and extra stuff added
			$this_arg = $o->get_option_prefix() . $o->get_option_value($i) . $o->get_option_postfix();
			$this_arg_description = $o->get_name() . ": " . $o->get_option_name($i);

			if(($cut_point = strpos($this_arg_description, "(")) > 1 && strpos($this_arg_description, ")") > $cut_point)
			{
				$this_arg_description = substr($this_arg_description, 0, $cut_point);
			}
			array_push($option_args, $this_arg);
			array_push($option_args_description, $this_arg_description);
		}

		if($i > 1)
		{
			$description_separate = " - ";
		}

		array_push($batch_all_args_real, $option_args);
		array_push($batch_all_args_description, $option_args_description);
	}

	$test_args = array();
	pts_all_combos($test_args, "", $batch_all_args_real, 0);

	$test_args_description = array();
	pts_all_combos($test_args_description, "", $batch_all_args_description, 0, $description_separate);

	return array($test_args, $test_args_description);
}
function pts_prompt_save_file_name($check_env = true, $to_run)
{
	// Prompt to save a file when running a test
	$proposed_name = null;
	$custom_title = null;

	if($check_env != false)
	{
		if(!empty($check_env) || ($check_env = getenv("TEST_RESULTS_NAME")) != false)
		{
			$custom_title = $check_env;
			$proposed_name = pts_input_string_to_identifier($check_env);
			//echo "Saving Results To: " . $proposed_name . "\n";
		}
	}

	if(pts_read_assignment("IS_BATCH_MODE") == false || pts_batch_prompt_save_name())
	{
		$is_reserved_word = pts_is_test($proposed_name) || pts_is_suite($proposed_name);

		while(empty($proposed_name) || $is_reserved_word || !pts_validate_save_file_name($proposed_name, $to_run))
		{
			if($is_reserved_word)
			{
				echo "\n\nThe name of the saved file cannot be the same as a test/suite: " . $proposed_name . "\n";
				$is_reserved_word = false;
			}
			else if(!pts_validate_save_file_name($proposed_name, $to_run))
			{
				echo "\n\n" . $proposed_name . " is associated with a different test/suite.\n";
			}

			echo "Enter a name to save these results: ";
			$proposed_name = trim(fgets(STDIN));
			$custom_title = $proposed_name;
			$proposed_name = pts_input_string_to_identifier($proposed_name);

			$is_reserved_word = pts_is_run_object($proposed_name);
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

	return array($proposed_name, $custom_title);
}
function pts_validate_save_file_name($proposed_save_name, $to_run)
{
	$is_validated = true;

	if(is_file(SAVE_RESULTS_DIR . $proposed_save_name . "/composite.xml") && !pts_is_assignment("AUTO_SAVE_NAME"))
	{
		$xml_parser = new pts_results_tandem_XmlReader($proposed_save_name);
		$test_suite = $xml_parser->getXMLValue(P_RESULTS_SUITE_NAME);

		if(!pts_is_assignment("GLOBAL_COMPARISON"))
		{
			if($test_suite != $to_run && !pts_is_assignment("MULTI_TYPE_RUN"))
			{
				$is_validated = false;
			}
		}
	}

	return $is_validated;
}
function pts_promt_user_tags($default_tags = "")
{
	$tags_input = "";

	if(pts_read_assignment("IS_BATCH_MODE") == false)
	{
		echo "\nTags are optional and used on Phoronix Global for making it easy to share, search, and organize test results. Example tags could be the type of test performed (i.e. WINE tests) or the hardware used (i.e. Dual Core SMP).\n\nEnter the tags you wish to provide (separated by commas): ";
		$tags_input .= fgets(STDIN);

		if(function_exists("preg_replace"))
		{
			$tags_input = preg_replace("/[^a-zA-Z0-9s, -]/", "", $tags_input);
		}
		else
		{
			$tags_input = pts_remove_chars($tags_input, true, true, true);
		}

		$tags_input = trim($tags_input);
	}

	if(empty($tags_input))
	{
		if(!is_array($default_tags) && !empty($default_tags))
		{
			$default_tags = array($default_tags);
		}

		$tags_input = pts_global_auto_tags($default_tags);
	}

	return $tags_input;
}
function pts_add_test_note($note)
{
	pts_test_note("ADD", $note);
}
function pts_test_note($process, $value = null)
{
	static $note_r;
	$return = null;

	if(empty($note_r))
	{
		$note_r = array();
	}

	switch($process)
	{
		case "ADD":
			if(!empty($value) && !in_array($value, $note_r))
			{
				array_push($note_r, $value);
			}
			break;
		case "TO_STRING":
			$return = implode(". \n", $note_r);
			break;
	}

	return $return;
}
function pts_generate_test_notes($test_type)
{
	static $check_processes = null;

	if(empty($check_processes) && is_file(STATIC_DIR . "process-reporting-checks.txt"))
	{
		$word_file = trim(file_get_contents(STATIC_DIR . "process-reporting-checks.txt"));
		$processes_r = array_map("trim", explode("\n", $word_file));
		$check_processes = array();

		foreach($processes_r as $p)
		{
			$p = explode("=", $p);
			$p_title = trim($p[0]);
			$p_names = array_map("trim", explode(",", $p[1]));

			$check_processes[$p_title] = array();

			foreach($p_names as $p_name)
			{
				array_push($check_processes[$p_title], $p_name);
			}
		}
	}

	if(!IS_BSD)
	{
		pts_add_test_note(pts_process_running_string($check_processes));
	}

	// Check if Security Enhanced Linux was enforcing, permissive, or disabled
	if(is_file("/etc/sysconfig/selinux") && is_readable("/boot/grub/menu.lst"))
	{
		$selinux_file = file_get_contents("/etc/sysconfig/selinux");
		if(stripos($selinux_file, "selinux=disabled") === false)
		{
			pts_add_test_note("SELinux was enabled.");
		}
	}
	else if(is_file("/boot/grub/menu.lst") && is_readable("/boot/grub/menu.lst"))
	{
		$grub_file = file_get_contents("/boot/grub/menu.lst");
		if(stripos($grub_file, "selinux=1") !== false)
		{
			pts_add_test_note("SELinux was enabled.");
		}
	}

	// Power Saving Technologies?
	pts_add_test_note(hw_cpu_power_savings_enabled());
	pts_add_test_note(hw_sys_power_mode());
	pts_add_test_note(sw_os_virtualized_mode());

	if($test_type == "Graphics" || $test_type == "System")
	{
		$aa_level = hw_gpu_aa_level();
		$af_level = hw_gpu_af_level();

		if(!empty($aa_level))
		{
			pts_add_test_note("Antialiasing: " . $aa_level);
		}
		if(!empty($af_level))
		{
			pts_add_test_note("Anisotropic Filtering: " . $af_level);
		}
	}

	return pts_test_note("TO_STRING");
}
function pts_input_string_to_identifier($input)
{
	$input = pts_swap_variables($input, "pts_user_runtime_variables");
	$input = trim(str_replace(array(' ', '/', '&', '\''), "", strtolower($input)));

	return $input;
}
function pts_verify_test_installation($identifiers)
{
	// Verify a test is installed
	$identifiers_o = $identifiers;
	$tests = array();
	$identifiers = pts_to_array($identifiers);

	foreach($identifiers as $identifier)
	{
		foreach(pts_contained_tests($identifier) as $this_test)
		{
			array_push($tests, $this_test);
		}
	}

	$tests = array_unique($tests);
	$needs_installing = array();
	$pass_count = 0;
	$fail_count = 0;
	$valid_op = true;

	foreach($tests as $test)
	{
		if(!pts_test_installed($test))
		{
			$fail_count++;
			if(pts_test_supported($test))
			{
				array_push($needs_installing, $test);
			}
		}
		else
		{
			$pass_count++;
		}
	}

	if($fail_count > 0)
	{
		$needs_installing = array_unique($needs_installing);
	
		if(count($needs_installing) > 0)
		{
			if(count($needs_installing) == 1)
			{
				echo pts_string_header($needs_installing[0] . " isn't installed.\nTo install, run: phoronix-test-suite install " . $needs_installing[0]);
			}
			else
			{
				$message = "Multiple tests need to be installed before proceeding:\n\n";
				foreach($needs_installing as $single_package)
				{
					$message .= "- " . $single_package . "\n";
				}

				$message .= "\nTo install these tests, run: phoronix-test-suite install " . implode(" ", $identifiers);

				echo pts_string_header($message);
			}
		}

		if(pts_is_test($identifiers_o))
		{
			$valid_op = false;
		}
	}

	return $valid_op;
}
function pts_recurse_call_tests($tests_to_run, &$tandem_xml = "", $results_identifier = "")
{
	for($i = 0; $i < count($tests_to_run); $i++)
	{
		$to_run = $tests_to_run[$i]->get_identifier();

		if(pts_is_suite($to_run))
		{
			$xml_parser = new pts_suite_tandem_XmlReader($to_run);
			$tests_in_suite = $xml_parser->getXMLArrayValues(P_SUITE_TEST_NAME);
			$sub_arguments = $xml_parser->getXMLArrayValues(P_SUITE_TEST_ARGUMENTS);
			$sub_arguments_description = $xml_parser->getXMLArrayValues(P_SUITE_TEST_DESCRIPTION);

			$suite_tests = array();
			for($i = 0; $i < count($tests_in_suite); $i++)
			{
				array_push($suite_tests, new pts_test_run_request($tests_in_suite[$i], $sub_arguments[$i], $sub_arguments_description[$i]));
			}

			pts_recurse_call_tests($suite_tests, $tandem_xml, $results_identifier);
		}
		else if(pts_is_test($to_run))
		{
			$test_result = pts_run_test($to_run, $tests_to_run[$i]->get_arguments(), $tests_to_run[$i]->get_arguments_description());

			if($test_result instanceof pts_test_result)
			{
				$end_result = $test_result->get_result();

				if(!empty($results_identifier) && count($test_result) > 0 && ((is_numeric($end_result) && $end_result > 0) || (!is_numeric($end_result) && strlen($end_result) > 2)))
				{
					pts_record_test_result($tandem_xml, $test_result, $results_identifier, pts_request_new_id());
				}
			}

			if($i != (count($tests_to_run) - 1))
			{
				sleep(pts_read_user_config(P_OPTION_TEST_SLEEPTIME, 5));
			}
		}
	}
}
function pts_record_test_result(&$tandem_xml, $result, $identifier, $tandem_id = 128)
{
	// Do the actual recording of the test result and other relevant information for the given test

	$tandem_xml->addXmlObject(P_RESULTS_TEST_TITLE, $tandem_id, $result->get_attribute("TEST_TITLE"));
	$tandem_xml->addXmlObject(P_RESULTS_TEST_VERSION, $tandem_id, $result->get_attribute("TEST_VERSION"));
	$tandem_xml->addXmlObject(P_RESULTS_TEST_ATTRIBUTES, $tandem_id, $result->get_attribute("TEST_DESCRIPTION"));
	$tandem_xml->addXmlObject(P_RESULTS_TEST_SCALE, $tandem_id, $result->get_result_scale());
	$tandem_xml->addXmlObject(P_RESULTS_TEST_PROPORTION, $tandem_id, $result->get_result_proportion());
	$tandem_xml->addXmlObject(P_RESULTS_TEST_RESULTFORMAT, $tandem_id, $result->get_result_format());
	$tandem_xml->addXmlObject(P_RESULTS_TEST_TESTNAME, $tandem_id, $result->get_attribute("TEST_IDENTIFIER"));
	$tandem_xml->addXmlObject(P_RESULTS_TEST_ARGUMENTS, $tandem_id, $result->get_attribute("EXTRA_ARGUMENTS"));
	$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_IDENTIFIER, $tandem_id, $identifier, 5);
	$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_VALUE, $tandem_id, $result->get_result(), 5);
	$tandem_xml->addXmlObject(P_RESULTS_RESULTS_GROUP_RAW, $tandem_id, $result->get_trial_results_string(), 5);

	pts_set_assignment("TEST_RAN", true);
}
function pts_save_test_file($proposed_name, &$results = null, $raw_text = null)
{
	// Save the test file
	$j = 1;
	while(is_file(SAVE_RESULTS_DIR . $proposed_name . "/test-" . $j . ".xml"))
	{
		$j++;
	}

	$real_name = $proposed_name . "/test-" . $j . ".xml";

	if($results != null)
	{
		$r_file = $results->getXML();
	}
	else if($raw_text != null)
	{
		$r_file = $raw_text;
	}
	else
	{
		return false;
	}

	pts_save_result($real_name, $r_file);

	if(!is_file(SAVE_RESULTS_DIR . $proposed_name . "/composite.xml"))
	{
		pts_save_result($proposed_name . "/composite.xml", file_get_contents(SAVE_RESULTS_DIR . $real_name));
	}
	else
	{
		// Merge Results
		$MERGED_RESULTS = pts_merge_test_results(file_get_contents(SAVE_RESULTS_DIR . $proposed_name . "/composite.xml"), file_get_contents(SAVE_RESULTS_DIR . $real_name));
		pts_save_result($proposed_name . "/composite.xml", $MERGED_RESULTS);
	}
	return $real_name;
}
function pts_run_test($test_identifier, $extra_arguments = "", $arguments_description = "")
{
	// Do the actual test running process
	$pts_test_result = new pts_test_result();

	if(pts_process_active($test_identifier))
	{
		echo "\nThis test (" . $test_identifier . ") is already running.\n";
		return $pts_test_result;
	}
	pts_process_register($test_identifier);
	$test_directory = TEST_ENV_DIR . $test_identifier . "/";

	$xml_parser = new pts_test_tandem_XmlReader($test_identifier);
	$execute_binary = $xml_parser->getXMLValue(P_TEST_EXECUTABLE);
	$test_title = $xml_parser->getXMLValue(P_TEST_TITLE);
	$test_version = $xml_parser->getXMLValue(P_TEST_VERSION);
	$times_to_run = intval($xml_parser->getXMLValue(P_TEST_RUNCOUNT));
	$ignore_first_run = $xml_parser->getXMLValue(P_TEST_IGNOREFIRSTRUN);
	$pre_run_message = $xml_parser->getXMLValue(P_TEST_PRERUNMSG);
	$post_run_message = $xml_parser->getXMLValue(P_TEST_POSTRUNMSG);
	$result_scale = $xml_parser->getXMLValue(P_TEST_SCALE);
	$result_proportion = $xml_parser->getXMLValue(P_TEST_PROPORTION);
	$result_format = $xml_parser->getXMLValue(P_TEST_RESULTFORMAT);
	$result_quantifier = $xml_parser->getXMLValue(P_TEST_QUANTIFIER);
	$arg_identifier = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_IDENTIFIER);
	$execute_path = $xml_parser->getXMLValue(P_TEST_POSSIBLEPATHS);
	$default_arguments = $xml_parser->getXMLValue(P_TEST_DEFAULTARGUMENTS);
	$test_type = $xml_parser->getXMLValue(P_TEST_HARDWARE_TYPE);
	$root_required = $xml_parser->getXMLValue(P_TEST_ROOTNEEDED) == "TRUE";

	if(($test_type == "Graphics" && getenv("DISPLAY") == false) || getenv("NO_" . strtoupper($test_type) . "_TESTS") != false)
	{
		return $pts_test_result;
	}

	if(empty($result_format))
	{
		$result_format = "BAR_GRAPH";
	}
	else if(strlen($result_format) > 6 && substr($result_format, 0, 6) == "MULTI_") // Currently tests that output multiple results in one run can only be run once
	{
		$times_to_run = 1;
	}
	
	if(empty($times_to_run) || !is_int($times_to_run))
	{
		$times_to_run = 3;
	}
	if(($force_runs = getenv("FORCE_TIMES_TO_RUN")) != false)
	{
		if($force_runs > $times_to_run)
		{
			$times_to_run = $force_runs;
		}
	}

	if(!empty($test_type))
	{
		$test_name = "TEST_" . strtoupper($test_type);
		pts_set_assignment_once($test_name, 1);
	}

	if(empty($execute_binary))
	{
		$execute_binary = $test_identifier;
	}

	$execute_path_check = explode(",", $execute_path);
	array_push($execute_path_check, $test_directory);

	while(count($execute_path_check) > 0)
	{
		$path_check = trim(array_pop($execute_path_check));

		if(is_file($path_check . $execute_binary) || is_link($path_check . $execute_binary))
		{
			$to_execute = $path_check;
		}	
	}

	if(!isset($to_execute) || empty($to_execute))
	{
		echo "The test executable for " . $test_identifier . " could not be found. Skipping test.\n\n";
		return $pts_test_result;
	}

	if(pts_test_needs_updated_install($test_identifier))
	{
		echo pts_string_header("NOTE: This test installation is out of date.\nFor best results, the " . $test_title . " test should be re-installed.");
		// Auto reinstall
		//require_once(PTS_PATH . "pts-core/functions/pts-functions-install.php");
		//pts_install_test($test_identifier);
	}

	$pts_test_arguments = trim($default_arguments . " " . str_replace($default_arguments, "", $extra_arguments));
	$extra_runtime_variables = pts_run_additional_vars($test_identifier);

	// Start
	$pts_test_result->set_attribute("TEST_TITLE", $test_title);
	$pts_test_result->set_attribute("TEST_IDENTIFIER", $test_identifier);
	pts_module_process("__pre_test_run", $pts_test_result);

	$time_test_start = time();
	echo pts_call_test_script($test_identifier, "pre", "\nRunning Pre-Test Scripts...\n", $test_directory, $extra_runtime_variables);

	pts_user_message($pre_run_message);

	$runtime_identifier = pts_unique_runtime_identifier();

	$execute_binary_prepend = "";

	if($root_required)
	{
		$execute_binary_prepend = TEST_LIBRARIES_DIR . "root-access.sh";
	}

	if(!empty($execute_binary_prepend))
	{
		$execute_binary_prepend .= " ";
	}

	for($i = 0; $i < $times_to_run; $i++)
	{
		$benchmark_log_file = TEST_ENV_DIR . $test_identifier . "/" . $test_identifier . "-" . $runtime_identifier . "-" . ($i + 1) . ".log";
		$start_timer = TEST_LIBRARIES_DIR . "timer-start.sh";
		$stop_timer = TEST_LIBRARIES_DIR . "timer-stop.sh";
		$timed_kill = TEST_LIBRARIES_DIR . "timed-kill.sh";
		$test_extra_runtime_variables = array_merge($extra_runtime_variables, array("LOG_FILE" => $benchmark_log_file, "TIMER_START" => $start_timer, "TIMER_STOP" => $stop_timer, "TIMED_KILL" => $timed_kill, "PHP_BIN" => PHP_BIN));

		echo pts_string_header($test_title . " (Run " . ($i + 1) . " of " . $times_to_run . ")");
		$result_output = array();

		$test_results = pts_exec("cd " . $to_execute . " && " . $execute_binary_prepend . "./" . $execute_binary . " " . $pts_test_arguments, $test_extra_runtime_variables);

		if(strlen($test_results) < 10240)
		{
			echo $test_results;
		}

		if(is_file($benchmark_log_file) && trim($test_results) == "" && filesize($benchmark_log_file) < 10240)
		{
			echo file_get_contents($benchmark_log_file);
		}

		if(!($i == 0 && pts_string_bool($ignore_first_run) && $times_to_run > 1))
		{
			$test_extra_runtime_variables_post = $test_extra_runtime_variables;
			if(is_file(TEST_ENV_DIR . $test_identifier . "/pts-timer"))
			{
				$run_time = trim(file_get_contents(TEST_ENV_DIR . $test_identifier . "/pts-timer"));
				unlink(TEST_ENV_DIR . $test_identifier . "/pts-timer");

				if(is_numeric($run_time))
				{
					$test_extra_runtime_variables_post = array_merge($test_extra_runtime_variables_post, array("TIMER_RESULT" => $run_time));
				}
			}
			if(is_file($benchmark_log_file))
			{
				$test_results = "";
			}

			$test_results = pts_call_test_script($test_identifier, "parse-results", null, $test_results, $test_extra_runtime_variables_post);

			if(empty($test_results) && isset($run_time) && is_numeric($run_time))
			{
				$test_results = $run_time;
			}

			$validate_result = trim(pts_call_test_script($test_identifier, "validate-result", null, $test_results, $test_extra_runtime_variables_post));

			if(!empty($validate_result) && !pts_string_bool($validate_result))
			{
				$test_results = null;
			}

			if(!empty($test_results))
			{
				$pts_test_result->add_trial_run_result(trim($test_results));
			}
		}
		if($times_to_run > 1 && $i < ($times_to_run - 1))
		{
			echo pts_call_test_script($test_identifier, "interim", null, $test_directory, $extra_runtime_variables);
			pts_module_process("__interim_test_run", $pts_test_result);
			sleep(1); // Rest for a moment between tests
		}

		if(is_file($benchmark_log_file))
		{
			if(pts_is_assignment("TEST_RESULTS_IDENTIFIER") && (pts_string_bool(pts_read_user_config(P_OPTION_LOG_BENCHMARKFILES, "FALSE")) || pts_read_assignment("IS_PCQS_MODE") || pts_read_assignment("IS_BATCH_MODE")))
			{
				$backup_log_dir = SAVE_RESULTS_DIR . pts_read_assignment("SAVE_FILE_NAME") . "/benchmark-logs/" . pts_read_assignment("TEST_RESULTS_IDENTIFIER") . "/";
				$backup_filename = basename($benchmark_log_file);
				@mkdir($backup_log_dir, 0777, true);
				@copy($benchmark_log_file, $backup_log_dir . $backup_filename);
			}

			@unlink($benchmark_log_file);
		}
	}

	echo pts_call_test_script($test_identifier, "post", null, $test_directory, $extra_runtime_variables);

	// End
	$time_test_end = time();

	if(is_file($test_directory . "/pts-test-note"))
	{
		pts_add_test_note(trim(@file_get_contents($test_directory . "/pts-test-note")));
		unlink($test_directory . "pts-test-note");
	}
	if(empty($result_scale) && is_file($test_directory . "pts-results-scale"))
	{
		$result_scale = trim(@file_get_contents($test_directory . "pts-results-scale"));
		unlink($test_directory . "pts-results-scale");
	}
	if(empty($result_quantifier) && is_file($test_directory . "pts-results-quantifier"))
	{
		$result_quantifier = trim(@file_get_contents($test_directory . "pts-results-quantifier"));
		unlink($test_directory . "pts-results-quantifier");
	}
	if(empty($test_version) && is_file($test_directory . "pts-test-version"))
	{
		$test_version = @file_get_contents($test_directory . "pts-test-version");
		unlink($test_directory . "pts-test-version");
	}
	if(empty($arguments_description))
	{
		$default_test_descriptor = $xml_parser->getXMLValue(P_TEST_SUBTITLE);

		if(!empty($default_test_descriptor))
		{
			$arguments_description = $default_test_descriptor;
		}
		else if(is_file($test_directory . "pts-test-description"))
		{
			$arguments_description = @file_get_contents($test_directory . "pts-test-description");
			unlink($test_directory . "pts-test-description");
		}
		else
		{
			$arguments_description = "Phoronix Test Suite v" . PTS_VERSION;
		}
	}
	foreach(pts_env_variables() as $key => $value)
	{
		$arguments_description = str_replace("$" . $key, $value, $arguments_description);
	}
	foreach(pts_env_variables() as $key => $value)
	{
		if($key != "VIDEO_MEMORY" && $key != "NUM_CPU_CORES" && $key != "NUM_CPU_JOBS")
		{
			$extra_arguments = str_replace("$" . $key, $value, $extra_arguments);
		}
	}

	$return_string = $test_title . ":\n";
	$return_string .= $arguments_description . "\n";

	if(!empty($arguments_description))
	{
		$return_string .= "\n";
	}

	// Result Calculation
	$pts_test_result->set_attribute("TEST_DESCRIPTION", $arguments_description);
	$pts_test_result->set_attribute("TEST_VERSION", $test_version);
	$pts_test_result->set_attribute("EXTRA_ARGUMENTS", $extra_arguments);
	$pts_test_result->set_result_format($result_format);
	$pts_test_result->set_result_proportion($result_proportion);
	$pts_test_result->set_result_scale($result_scale);
	$pts_test_result->set_result_quantifier($result_quantifier);
	$pts_test_result->calculate_end_result($return_string); // Process results

	if(!empty($return_string))
	{
		echo $this_result = pts_string_header($return_string, "#");
		pts_text_save_buffer($this_result);
	}
	else
	{
		echo "\n\n";
	}

	pts_user_message($post_run_message);

	pts_process_remove($test_identifier);
	pts_module_process("__post_test_run", $pts_test_result);
	pts_test_refresh_install_xml($test_identifier, ($time_test_end - $time_test_start));

	if($result_format == "NO_RESULT")
	{
		$pts_test_result = false;
	}

	return $pts_test_result;
}
function pts_global_auto_tags($extra_attr = null)
{
	// Generate automatic tags for the system, used for Phoronix Global
	$tags_array = array();

	if(!empty($extra_attr) && is_array($extra_attr))
	{
		foreach($extra_attr as $attribute)
		{
			array_push($tags_array, $attribute);
		}
	}

	switch(hw_cpu_core_count())
	{
		case 1:
			array_push($tags_array, "Single Core");
			break;
		case 2:
			array_push($tags_array, "Dual Core");
			break;
		case 4:
			array_push($tags_array, "Quad Core");
			break;
		case 8:
			array_push($tags_array, "Octal Core");
			break;
	}

	$cpu_type = hw_cpu_string();
	if(strpos($cpu_type, "Intel") !== false)
	{
		array_push($tags_array, "Intel");
	}
	else if(strpos($cpu_type, "AMD") !== false)
	{
		array_push($tags_array, "AMD");
	}
	else if(strpos($cpu_type, "VIA") !== false)
	{
		array_push($tags_array, "VIA");
	}

	$gpu_type = hw_gpu_string();
	if(strpos($cpu_type, "ATI") !== false)
	{
		array_push($tags_array, "ATI");
	}
	else if(strpos($cpu_type, "NVIDIA") !== false)
	{
		array_push($tags_array, "NVIDIA");
	}

	if(sw_os_architecture() == "x86_64" && IS_LINUX)
	{
		array_push($tags_array, "64-bit Linux");
	}

	$os = sw_os_release();
	if($os != "Unknown")
	{
		array_push($tags_array, $os);
	}

	return implode(", ", $tags_array);
}
function pts_all_combos(&$return_arr, $current_string, $options, $counter, $delimiter = " ")
{
	// In batch mode, find all possible combinations for test options
	if(count($options) <= $counter)
	{
		array_push($return_arr, trim($current_string));
	}
	else
        {
		foreach($options[$counter] as $single_option)
		{
			$new_current_string = $current_string;

			if(strlen($new_current_string) > 0)
			{
				$new_current_string .= $delimiter;
			}

			$new_current_string .= $single_option;

			pts_all_combos($return_arr, $new_current_string, $options, $counter + 1, $delimiter);
		}
	}
}
function pts_auto_process_test_option($identifier, &$option_names, &$option_values)
{
	// Some test items have options that are dynamically built
	switch($identifier)
	{
		case "auto-resolution":
			// Base options off available screen resolutions
			if(count($option_names) == 1 && count($option_values) == 1)
			{
				$available_video_modes = hw_gpu_xrandr_available_modes();
				$format_name = $option_names[0];
				$format_value = $option_values[0];
				$option_names = array();
				$option_values = array();

				foreach($available_video_modes as $video_mode)
				{
					$this_name = str_replace("\$VIDEO_WIDTH", $video_mode[0], $format_name);
					$this_name = str_replace("\$VIDEO_HEIGHT", $video_mode[1], $this_name);

					$this_value = str_replace("\$VIDEO_WIDTH", $video_mode[0], $format_value);
					$this_value = str_replace("\$VIDEO_HEIGHT", $video_mode[1], $this_value);

					array_push($option_names, $this_name);
					array_push($option_values, $this_value);
				}
			}
			break;
		case "auto-disk-partitions":
			// Base options off available disk partitions
			$all_devices = array_merge(glob("/dev/hd*"), glob("/dev/sd*"));
			$all_devices_count = count($all_devices);

			for($i = 0; $i < $all_devices_count; $i++)
			{
				$last_char = substr($all_devices[$i], -1);

				if(!is_numeric($last_char))
				{
					unset($all_devices[$i]);
				}
			}

			$option_values = array();
			foreach($all_devices as $partition)
			{
				array_push($option_values, $partition);
			}
			$option_names = $option_values;
			break;
		case "auto-disks":
			// Base options off attached disks
			$all_devices = array_merge(glob("/dev/hd*"), glob("/dev/sd*"));
			$all_devices_count = count($all_devices);

			for($i = 0; $i < $all_devices_count; $i++)
			{
				$last_char = substr($all_devices[$i], -1);

				if(is_numeric($last_char))
				{
					unset($all_devices[$i]);
				}
			}

			$option_values = array();
			foreach($all_devices as $disk)
			{
				array_push($option_values, $disk);
			}
			$option_names = $option_values;
			break;
		case "auto-removable-media":
			$removable_media = glob("/media/*/");

			for($i = 0; $i < count($removable_media); $i++)
			{
				if(is_dir($removable_media[$i])) // add more checks later on
				{
					array_push($option_names, $removable_media[$i]);
					array_push($option_values, $removable_media[$i]);
				}
			}
			break;
		case "auto-file-select":
			$names = $option_names;
			$values = $option_values;
			$option_names = array();
			$option_values = array();

			for($i = 0; $i < count($names) && $i < count($values); $i++)
			{
				if(is_file($values[$i]))
				{
					array_push($option_names, $names[$i]);
					array_push($option_values, $values[$i]);
				}
			}
			break;
		case "auto-directory-select":
			$names = $option_names;
			$values = $option_values;
			$option_names = array();
			$option_values = array();

			for($i = 0; $i < count($names) && $i < count($values); $i++)
			{
				if(is_dir($values[$i]))
				{
					array_push($option_names, $names[$i]);
					array_push($option_values, $values[$i]);
				}
			}
			break;
	}
}
function pts_test_options($identifier)
{
	$xml_parser = new pts_test_tandem_XmlReader($identifier);
	$settings_name = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_DISPLAYNAME);
	$settings_argument_prefix = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_ARGPREFIX);
	$settings_argument_postfix = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_ARGPOSTFIX);
	$settings_identifier = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_IDENTIFIER);
	$settings_default = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_DEFAULTENTRY);
	$settings_menu = $xml_parser->getXMLArrayValues(P_TEST_OPTIONS_MENU_GROUP);

	$test_options = array();

	for($option_count = 0; $option_count < count($settings_name); $option_count++)
	{
		$xml_parser = new tandem_XmlReader($settings_menu[$option_count]);
		$option_names = $xml_parser->getXMLArrayValues(S_TEST_OPTIONS_MENU_GROUP_NAME);
		$option_values = $xml_parser->getXMLArrayValues(S_TEST_OPTIONS_MENU_GROUP_VALUE);
		pts_auto_process_test_option($settings_identifier[$option_count], $option_names, $option_values);

		$user_option = new pts_test_option($settings_identifier[$option_count], $settings_name[$option_count]);
		$prefix = $settings_argument_prefix[$option_count];

		$user_option->set_option_prefix($prefix);
		$user_option->set_option_postfix($settings_argument_postfix[$option_count]);

		for($i = 0; $i < count($option_names) && $i < count($option_values); $i++)
		{
			$user_option->add_option($option_names[$i], $option_values[$i]);
		}

		$user_option->set_option_default($settings_default[$option_count]);

		array_push($test_options, $user_option);
	}

	return $test_options;
}

?>
