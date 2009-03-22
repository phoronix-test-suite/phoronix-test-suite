<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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
function pts_auto_process_test_option($test_identifier, $option_identifier, &$option_names, &$option_values)
{
	// Some test items have options that are dynamically built
	switch($option_identifier)
	{
		case "auto-resolution":
			// Base options off available screen resolutions
			if(count($option_names) == 1 && count($option_values) == 1)
			{
				$available_video_modes = hw_gpu_available_modes();
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

			if($option_identifier == "auto-disk-mount-points")
			{
				$partitions_d = $option_values;
				$option_values = array();
				$option_names = array();

				if(is_file("/proc/mounts"))
				{
					$mounts = file_get_contents("/proc/mounts");
				}
				else
				{
					$mounts = null;
				}

				array_push($option_values, "");
				array_push($option_names, "Default Test Directory");

				foreach($partitions_d as $partition_d)
				{
					$mount_point = substr(($a = substr($mounts, strpos($mounts, $partition_d) + strlen($partition_d) + 1)), 0, strpos($a, " "));

					if(is_dir($mount_point) && $mount_point != "/boot")
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
		pts_auto_process_test_option($identifier, $settings_identifier[$option_count], $option_names, $option_values);

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
