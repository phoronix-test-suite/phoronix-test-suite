<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-includes-run_setup.php: Test options functions needed for running tests/suites.

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

function pts_prompt_test_options($identifier)
{
	$user_args = array();
	$text_args = array();

	if(pts_is_assignment("AUTOMATED_MODE"))
	{
		$preset_selections = pts_read_assignment("AUTO_TEST_OPTION_SELECTIONS");
	}
	else if(($cli_presets_env = pts_client::read_env("PRESET_OPTIONS")) != false)
	{
		// To specify test options externally from an environment variable
		// i.e. PRESET_OPTIONS="stream.run-type=Add" ./phoronix-test-suite benchmark stream

		pts_set_assignment("CLI_PRESET_OPTIONS", true);
		$cli_presets = pts_client::parse_value_string_double_identifier($cli_presets_env);
	}

	foreach(pts_test_options($identifier) as $i => $o)
	{
		if($i == 0)
		{
			echo pts_string_header("Test Configuration: " . pts_test_identifier_to_name($identifier));
		}

		$option_identifier = $o->get_identifier();

		if($o->option_count() == 0)
		{
			// User inputs their option
			if(pts_is_assignment("AUTOMATED_MODE") && isset($preset_selections[$identifier][$option_identifier]))
			{
				$value = $preset_selections[$identifier][$option_identifier];
			}
			else if(pts_is_assignment("CLI_PRESET_OPTIONS") && isset($cli_presets[$identifier][$option_identifier]))
			{
				$value = $cli_presets[$identifier][$option_identifier];
			}
			else
			{
				echo "\n" . $o->get_name() . "\n";
				$value = pts_user_io::prompt_user_input("Enter Value");
			}

			array_push($text_args, array($o->format_option_display_from_input($value)));
			array_push($user_args, array($o->format_option_value_from_input($value)));
		}
		else
		{
			// Have the user select the desired option
			if(pts_is_assignment("AUTOMATED_MODE") && isset($preset_selections[$identifier][$option_identifier]))
			{
				$bench_choice = $preset_selections[$identifier][$option_identifier];
			}
			else if(pts_is_assignment("CLI_PRESET_OPTIONS") && isset($cli_presets[$identifier][$option_identifier]))
			{
				$bench_choice = $cli_presets[$identifier][$option_identifier];
			}
			else
			{
				$option_names = $o->get_all_option_names();

				if(count($option_names) > 1)
				{
					echo "\n" . $o->get_name() . ":\n";
					array_push($option_names, "Test All Options");
				}

				$bench_choice = pts_user_io::prompt_text_menu("Enter Your Choice", $option_names, true, true);
			}

			$bench_choice = $o->parse_selection_choice_input($bench_choice);

			// Format the selected option(s)
			$option_args = array();
			$option_args_description = array();

			foreach($bench_choice as $c)
			{
				array_push($option_args, $o->format_option_value_from_select($c));
				array_push($option_args_description, $o->format_option_display_from_select($c));
			}

			array_push($text_args, $option_args_description);
			array_push($user_args, $option_args);
		}
	}

	$test_args = array();
	$test_args_description = array();

	pts_all_combos($test_args, "", $user_args, 0);
	pts_all_combos($test_args_description, "", $text_args, 0, " - ");

	return array($test_args, $test_args_description);
}
function pts_defaults_test_options($identifier)
{
	// Defaults mode for single test
	$all_args_real = array();
	$all_args_description = array();

	foreach(pts_test_options($identifier) as $o)
	{
		$option_args = array();
		$option_args_description = array();

		$default_entry = $o->get_option_default();

		if($o->option_count() == 2)
		{
			foreach(array(0, 1) as $i)
			{
				array_push($option_args, $o->format_option_value_from_select($i));
				array_push($option_args_description, $o->format_option_display_from_select($i));
			}
		}
		else
		{
			array_push($option_args, $o->format_option_value_from_select($default_entry));
			array_push($option_args_description, $o->format_option_display_from_select($default_entry));
		}

		array_push($all_args_real, $option_args);
		array_push($all_args_description, $option_args_description);
	}

	$test_args = array();
	$test_args_description = array();

	pts_all_combos($test_args, "", $all_args_real, 0);
	pts_all_combos($test_args_description, "", $all_args_description, 0, " - ");

	return array($test_args, $test_args_description);
}
function pts_generate_batch_run_options($identifier)
{
	// Batch mode for single test
	$batch_all_args_real = array();
	$batch_all_args_description = array();

	foreach(pts_test_options($identifier) as $o)
	{
		$option_args = array();
		$option_args_description = array();
		$option_count = $o->option_count();

		for($i = 0; $i < $option_count; $i++)
		{
			array_push($option_args, $o->format_option_value_from_select($i));
			array_push($option_args_description, $o->format_option_display_from_select($i));
		}

		array_push($batch_all_args_real, $option_args);
		array_push($batch_all_args_description, $option_args_description);
	}

	$test_args = array();
	$test_args_description = array();

	pts_all_combos($test_args, "", $batch_all_args_real, 0);
	pts_all_combos($test_args_description, "", $batch_all_args_description, 0, " - ");

	return array($test_args, $test_args_description);
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

			if(!empty($new_current_string))
			{
				$new_current_string .= $delimiter;
			}

			$new_current_string .= $single_option;

			pts_all_combos($return_arr, $new_current_string, $options, $counter + 1, $delimiter);
		}
	}
}
function pts_auto_process_test_option($test_identifier, $option_identifier, &$option_names, &$option_values)
{
	// Some test items have options that are dynamically built
	switch($option_identifier)
	{
		case "auto-resolution":
			// Base options off available screen resolutions
			if(count($option_names) == 1 && count($option_values) == 1)
			{
				$available_video_modes = PTS_MODE == "CLIENT" ? phodevi::read_property("gpu", "available-modes") : array();

				if(empty($available_video_modes))
				{
					// Use hard-coded defaults
					$available_video_modes = array(array(800, 600), array(1024, 768), array(1280, 1024), array(1280, 960), 
						array(1400, 1050), array(1680, 1050), array(1600, 1200), array(1920, 1080), array(2560, 1600));
				}

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
		case "auto-disk-mount-points":
			// Base options off available disk partitions
			if(PTS_MODE != "CLIENT")
			{
				echo "ERROR: This option is not supported in this configuration.";
				return;
			}

			$all_devices = array_merge(pts_file_io::glob("/dev/hd*"), pts_file_io::glob("/dev/sd*"));

			foreach($all_devices as &$device)
			{
				$last_char = substr($device, -1);

				if(!is_numeric($last_char))
				{
					unset($device);
				}
			}

			$option_values = array();
			foreach($all_devices as $partition)
			{
				array_push($option_values, $partition);
			}

			if($option_identifier == "auto-disk-mount-points")
			{
				$partitions_d = $option_values;
				$option_values = array();
				$option_names = array();

				$mounts = is_file("/proc/mounts") ? file_get_contents("/proc/mounts") : null;

				array_push($option_values, "");
				array_push($option_names, "Default Test Directory");

				foreach($partitions_d as $partition_d)
				{
					$mount_point = substr(($a = substr($mounts, strpos($mounts, $partition_d) + strlen($partition_d) + 1)), 0, strpos($a, " "));

					if(is_dir($mount_point) && is_writable($mount_point) && $mount_point != "/boot")
					{
						array_push($option_values, $mount_point);
						array_push($option_names, $mount_point . " [" . $partition_d . "]");
					}
				}
			}
			else
			{
				$option_names = $option_values;
			}

			break;
		case "auto-disks":
			// Base options off attached disks
			if(PTS_MODE != "CLIENT")
			{
				echo "ERROR: This option is not supported in this configuration.";
				return;
			}

			$all_devices = array_merge(pts_file_io::glob("/dev/hd*"), pts_file_io::glob("/dev/sd*"));

			foreach($all_devices as &$device)
			{
				$last_char = substr($device, -1);

				if(is_numeric($last_char))
				{
					unset($device);
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
			if(PTS_MODE != "CLIENT")
			{
				echo "ERROR: This option is not supported in this configuration.";
				return;
			}

			foreach(array_merge(pts_file_io::glob("/media/*/"), pts_file_io::glob("/Volumes/*/")) as $media_check)
			{
				if(is_dir($media_check) && is_writable($media_check)) // add more checks later on
				{
					array_push($option_names, $media_check);
					array_push($option_values, $media_check);
				}
			}
			break;
		case "auto-file-select":
			if(PTS_MODE != "CLIENT")
			{
				echo "ERROR: This option is not supported in this configuration.";
				return;
			}

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
			if(PTS_MODE != "CLIENT")
			{
				echo "ERROR: This option is not supported in this configuration.";
				return;
			}

			$names = $option_names;
			$values = $option_values;
			$option_names = array();
			$option_values = array();

			for($i = 0; $i < count($names) && $i < count($values); $i++)
			{
				if(is_dir($values[$i]) && is_writable($removable_media[$i]))
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

	$key_name = substr(P_TEST_OPTIONS_MENU_GROUP_NAME, strlen(P_TEST_OPTIONS_MENU_GROUP) + 1);
	$key_value = substr(P_TEST_OPTIONS_MENU_GROUP_VALUE, strlen(P_TEST_OPTIONS_MENU_GROUP) + 1);

	foreach(array_keys($settings_name) as $option_count)
	{
		$xml_parser = new tandem_XmlReader($settings_menu[$option_count]);
		$option_names = $xml_parser->getXMLArrayValues($key_name);
		$option_values = $xml_parser->getXMLArrayValues($key_value);
		pts_auto_process_test_option($identifier, $settings_identifier[$option_count], $option_names, $option_values);

		$user_option = new pts_test_option($settings_identifier[$option_count], $settings_name[$option_count]);

		$user_option->set_option_prefix($settings_argument_prefix[$option_count]);
		$user_option->set_option_postfix($settings_argument_postfix[$option_count]);

		foreach(array_keys($option_names) as $i)
		{
			$user_option->add_option($option_names[$i], $option_values[$i]);
		}

		$user_option->set_option_default($settings_default[$option_count]);

		array_push($test_options, $user_option);
	}

	return $test_options;
}

?>
