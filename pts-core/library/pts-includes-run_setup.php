<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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

function pts_prompt_results_identifier($file_name = null, &$test_run_manager)
{
	// Prompt for a results identifier
	$results_identifier = null;
	$show_identifiers = array();
	$no_repeated_tests = true;

	if(pts_is_test_result($file_name))
	{
		$result_file = new pts_result_file($file_name);
		$current_identifiers = $result_file->get_system_identifiers();

		$result_objects = $result_file->get_result_objects();

		foreach(array_keys($result_objects) as $result_key)
		{
			$result_objects[$result_key] = $result_objects[$result_key]->get_comparison_hash(false);
		}

		foreach($test_run_manager->get_tests_to_run() as $run_request)
		{
			if(in_array($run_request->get_comparison_hash(), $result_objects))
			{
				$no_repeated_tests = false;
				break;
			}
		}
	}
	else
	{
		$current_identifiers = array();
	}

	if(pts_read_assignment("IS_BATCH_MODE") == false || pts_batch_prompt_test_identifier())
	{
		if(count($current_identifiers) > 0)
		{
			echo "\nCurrent Test Identifiers:\n";
			foreach($current_identifiers as $identifier)
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

			// TODO: add check that if a unique name is repeated, that the system software/hardware matches from the result_file
		}
		while(!$no_repeated_tests && in_array($results_identifier, $current_identifiers) && !pts_is_assignment("FINISH_INCOMPLETE_RUN"));
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
		while(($is_reserved_word = pts_is_run_object($proposed_name)) || empty($proposed_name))
		{
			if($is_reserved_word)
			{
				echo "\n\nThe name of the saved file cannot be the same as a test/suite: " . $proposed_name . "\n";
				$is_reserved_word = false;
			}

			echo "Enter a name to save these results: ";
			$proposed_name = trim(fgets(STDIN));
			$custom_title = $proposed_name;
			$proposed_name = pts_input_string_to_identifier($proposed_name);
		}

		if(!pts_validate_save_file_name($proposed_name, $to_run))
		{
			echo "\n\nNOTE: " . $proposed_name . " is associated with a different test/suite.\n";
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

	if(pts_is_assignment("AUTOMATED_MODE"))
	{
		$preset_selections = pts_read_assignment("AUTO_TEST_OPTION_SELECTIONS");
	}

	for($this_option_pos = 0; $this_option_pos < count($test_options); $this_option_pos++)
	{
		$o = $test_options[$this_option_pos];
		$option_count = $o->option_count();
		$option_identifier = $o->get_identifier();

		if($option_count == 0)
		{
			// User inputs their option
			if(pts_is_assignment("AUTOMATED_MODE") && isset($preset_selections[$identifier][$option_identifier]))
			{
				$value = $preset_selections[$identifier][$option_identifier];
			}
			else
			{
				do
				{
					echo "\n" . $o->get_name() . "\n" . "Enter Value: ";
					$value = trim(fgets(STDIN));
				}
				while(empty($value));
			}

			$user_args .= $o->format_option_value_from_input($value);
		}
		else
		{
			if($option_count == 1)
			{
				// Only one option in menu, so auto-select it
				$bench_choice = 0;
			}
			else
			{
				// Have the user select the desired option
				if(pts_is_assignment("AUTOMATED_MODE") && isset($preset_selections[$identifier][$option_identifier]))
				{
					$bench_choice = $preset_selections[$identifier][$option_identifier];
				}
				else
				{
					do
					{
						echo "\n" . $o->get_name() . ":\n";

						$i = 1;
						foreach($o->get_all_option_names() as $option_name)
						{
							echo $i . ": " . $option_name . "\n";
							$i++;
						}
						echo "\nEnter Your Choice: ";
						$bench_choice = trim(fgets(STDIN));
					}
					while(($bench_choice = $o->is_valid_select_choice($bench_choice)) === false);
				}
			}

			// Format the selected option
			$text_args .= $o->format_option_display_from_select($bench_choice);

			if($this_option_pos < (count($test_options) - 1))
			{
				$text_args .= " - ";
			}

			$user_args .= $o->format_option_value_from_select($bench_choice) . " ";
		}
	}

	return array($user_args, $text_args);
}
function pts_promt_user_tags($default_tags = "")
{
	$tags_input = "";

	if(pts_read_assignment("IS_BATCH_MODE") == false && pts_read_assignment("AUTOMATED_MODE") == false)
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
function pts_input_string_to_identifier($input)
{
	$input = pts_swap_variables($input, "pts_user_runtime_variables");
	$input = trim(str_replace(array(' ', '/', '&', '\''), "", strtolower($input)));

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

	if(phodevi::read_property("system", "kernel-architecture") == "x86_64" && IS_LINUX)
	{
		array_push($tags_array, "64-bit Linux");
	}

	$os = phodevi::read_property("system", "operating-system");
	if($os != "Unknown")
	{
		array_push($tags_array, $os);
	}

	return implode(", ", $tags_array);
}

?>
