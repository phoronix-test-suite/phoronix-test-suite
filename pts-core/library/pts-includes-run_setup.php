<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-includes-run_setup.php: Setup functions needed for running tests/suites.

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

function pts_prompt_results_identifier(&$test_run_manager)
{
	// Prompt for a results identifier
	$file_name = $test_run_manager->get_file_name();
	$results_identifier = null;
	$show_identifiers = array();
	$no_repeated_tests = true;

	if(pts_is_test_result($file_name))
	{
		$result_file = new pts_result_file($file_name);
		$current_identifiers = $result_file->get_system_identifiers();
		$current_hardware = $result_file->get_system_hardware();
		$current_software = $result_file->get_system_software();

		$result_objects = $result_file->get_result_objects();

		foreach(array_keys($result_objects) as $result_key)
		{
			$result_objects[$result_key] = $result_objects[$result_key]->get_comparison_hash(false);
		}

		foreach($test_run_manager->get_tests_to_run() as $run_request)
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

	if(pts_read_assignment("IS_BATCH_MODE") == false || pts_batch_prompt_test_identifier())
	{
		if(count($current_identifiers) > 0)
		{
			echo "\nCurrent Test Identifiers:\n";
			echo pts_text_list($current_identifiers);
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
				echo "Enter a unique name for this test run: ";
				$results_identifier = trim(str_replace(array('/'), "", pts_read_user_input()));
			}
			$times_tried++;

			$identifier_pos = (($p = array_search($results_identifier, $current_identifiers)) !== false ? $p : -1);
		}
		while((!$no_repeated_tests && $identifier_pos != -1 && !pts_is_assignment("FINISH_INCOMPLETE_RUN") && !pts_is_assignment("RECOVER_RUN")) || (isset($current_hardware[$identifier_pos]) && $current_hardware[$identifier_pos] != pts_hw_string()) || (isset($current_software[$identifier_pos]) && $current_software[$identifier_pos] != pts_sw_string()));
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
	$test_run_manager->set_results_identifier($results_identifier);

	return $results_identifier;
}
function pts_prompt_save_file_name(&$test_run_manager, $check_env = true)
{
	// Prompt to save a file when running a test
	$proposed_name = null;
	$custom_title = null;

	if($check_env)
	{
		if((!empty($check_env) && $check_env !== true) || ($check_env = pts_client::read_env("TEST_RESULTS_NAME")))
		{
			$custom_title = $check_env;
			$proposed_name = pts_input_string_to_identifier($check_env);
			//echo "Saving Results To: " . $proposed_name . "\n";
		}
	}

	if(pts_read_assignment("IS_BATCH_MODE") == false || pts_batch_prompt_save_name())
	{
		while(($is_reserved_word = pts_is_run_object($proposed_name)) || empty($proposed_name))
		{
			if($is_reserved_word)
			{
				echo "\n\nThe name of the saved file cannot be the same as a test/suite: " . $proposed_name . "\n";
				$is_reserved_word = false;
			}

			echo "Enter a name to save these results: ";
			$proposed_name = pts_read_user_input();
			$custom_title = $proposed_name;
			$proposed_name = pts_input_string_to_identifier($proposed_name);
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
	$test_run_manager->set_file_name($proposed_name);

	return array($proposed_name, $custom_title);
}
function pts_prompt_svg_result_options($svg_file)
{
	// Image graph result driven test selection
	$run_options = pts_parse_svg_options($svg_file);
	$test_to_run = null;

	if(($run_option_count = count($run_options)) > 0)
	{
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

					$run_choice = pts_read_user_input();
				}
				while($run_choice < 1 || $run_choice > $run_option_count);
				$test_to_run = $run_options[($run_choice - 1)][0];
			}
		}
	}

	return $test_to_run;
}
function pts_prompt_user_tags($default_tags = null)
{
	$tags_input = null;

	if(pts_read_assignment("IS_BATCH_MODE") == false && pts_read_assignment("AUTOMATED_MODE") == false)
	{
		$tags_input .= pts_text_input("Tags are optional and used on Phoronix Global for making it easy to share, search, and organize test results. Example tags could be the type of test performed (i.e. WINE tests) or the hardware used (i.e. Dual Core SMP).\n\nEnter the tags you wish to provide (separated by commas)", true);

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

	if($tags_input == null)
	{
		$default_tags = pts_to_array($default_tags);
		$tags_input = pts_global_auto_tags($default_tags);
	}

	return $tags_input;
}
function pts_parse_svg_options($svg_file)
{
	$svg_parser = new tandem_XmlReader($svg_file);
	$svg_test = array_pop($svg_parser->getStatement("Test"));
	$svg_identifier = array_pop($svg_parser->getStatement("Identifier"));

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

	return $run_options;
}
function pts_input_string_to_identifier($input)
{
	$input = pts_swap_variables($input, "pts_user_runtime_variables");
	$input = trim(str_replace(array(' ', '/', '&', '?', ':', '~', '\''), "", strtolower($input)));

	return $input;
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

	switch(phodevi::read_property("cpu", "core-count"))
	{
		case 1:
			array_push($tags_array, "Single Core");
			break;
		case 2:
			array_push($tags_array, "Dual Core");
			break;
		case 3:
			array_push($tags_array, "Triple Core");
			break;
		case 4:
			array_push($tags_array, "Quad Core");
			break;
		case 8:
			array_push($tags_array, "Octal Core");
			break;
	}

	$cpu_type = phodevi::read_property("cpu", "model");
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

	if(IS_ATI_GRAPHICS)
	{
		array_push($tags_array, "ATI");
	}
	else if(IS_NVIDIA_GRAPHICS)
	{
		array_push($tags_array, "NVIDIA");
	}

	if(phodevi::read_property("system", "kernel-architecture") == "x86_64")
	{
		array_push($tags_array, "64-bit");
	}

	array_push($tags_array, phodevi::read_property("system", "operating-system"));

	return implode(", ", $tags_array);
}

?>
