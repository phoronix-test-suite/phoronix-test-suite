<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2020 Phoronix Media
	Copyright (C) 2010 - 2020, Michael Larabel

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

class pts_test_run_options
{
	public static function header_print_handler(&$test_profile, &$did_print)
	{
		if(!$did_print)
		{
			pts_client::$display->test_run_configure($test_profile);
			$did_print = true;
		}
	}
	public static function prompt_user_options(&$test_profile, $preset_selections = null, $no_prompts = false)
	{
		$user_args = array();
		$text_args = array();

		if(($cli_presets_env = pts_env::read('PRESET_OPTIONS')) != false)
		{
			// To specify test options externally from an environment variable
			// i.e. PRESET_OPTIONS='stream.run-type=Add' ./phoronix-test-suite benchmark stream
			// The string format is <test-name>.<test-option-name-from-XML-file>=<test-option-value>
			// The test-name can either be the short/base name (e.g. stream) or the full identifier (pts/stream) without version postfix
			// Multiple preset options can be delimited with the PRESET_OPTIONS environment variable via a semicolon ;
			$preset_selections = pts_client::parse_value_string_double_identifier($cli_presets_env);
		}
		if(($cli_presets_env_values = pts_env::read('PRESET_OPTIONS_VALUES')) != false)
		{
			// To specify test options externally from an environment variable
			// i.e. PRESET_OPTIONS_VALUES='stream.run-type=Add' ./phoronix-test-suite benchmark stream
			// The string format is <test-name>.<test-option-name-from-XML-file>=<test-option-value>
			// The test-name can either be the short/base name (e.g. stream) or the full identifier (pts/stream) without version postfix
			// Multiple preset options can be delimited with the PRESET_OPTIONS environment variable via a semicolon ;
			$preset_selections_values = pts_client::parse_value_string_double_identifier($cli_presets_env_values);
		}


		$identifier_short = $test_profile->get_identifier_base_name();
		$identifier_full = $test_profile->get_identifier(false);
		$error_handle = null;
		$option_objects = $test_profile->get_test_option_objects(true, $error_handle);

		$did_print_header = false;
		if($error_handle)
		{
			self::header_print_handler($test_profile, $did_print_header);
			echo PHP_EOL . pts_client::$display->get_tab() . pts_client::cli_just_italic($error_handle) . PHP_EOL;
			return false;
		}

		foreach($option_objects as $i => $o)
		{
			$option_identifier = $o->get_identifier();

			if(!empty($preset_selections_values) && isset($preset_selections_values[$identifier_short][$option_identifier]))
			{
				$b = explode(',', $preset_selections_values[$identifier_short][$option_identifier]);
				foreach($b as &$a)
				{
					$a = $o->format_option_display_from_input($a);
				}
				$text_args[] = $b;

				$b = explode(',', $preset_selections_values[$identifier_short][$option_identifier]);
				foreach($b as &$a)
				{
					$a = $o->format_option_value_from_input($a);
				}
				$user_args[] = $b;
			}
			else if($o->option_count() == 0)
			{
				// User inputs their option as there is nothing to select
				if(isset($preset_selections[$identifier_short][$option_identifier]))
				{
					self::header_print_handler($test_profile, $did_print_header);
					$value = $preset_selections[$identifier_short][$option_identifier];
					echo PHP_EOL . '    Using Pre-Set Run Option: ' . $value . PHP_EOL;
				}
				else if(isset($preset_selections[$identifier_full][$option_identifier]))
				{
					self::header_print_handler($test_profile, $did_print_header);
					$value = $preset_selections[$identifier_full][$option_identifier];
					echo PHP_EOL . '    Using Pre-Set Run Option: ' . $value . PHP_EOL;
				}
				else if($no_prompts)
				{
					$value = null;
				}
				else
				{
					self::header_print_handler($test_profile, $did_print_header);
					echo PHP_EOL . pts_client::$display->get_tab() . pts_client::cli_just_bold($o->get_name()) . ($o->get_helper_message() ? ' [' . pts_client::cli_just_italic($o->get_helper_message()) . ']' : null) . PHP_EOL;
					if($o->get_identifier() == 'positive-number')
					{
						do
						{
							$value = pts_user_io::prompt_user_input('Enter Positive Number', false, false, pts_client::$display->get_tab());
						}
						while($value <= 0 || !is_numeric($value));
					}
					else
					{
						$value = pts_user_io::prompt_user_input('Enter Value', false, false, pts_client::$display->get_tab());
					}
				}

				$text_args[] = array($o->format_option_display_from_input($value));
				$user_args[] = array($o->format_option_value_from_input($value));
			}
			else
			{
				// Have the user select the desired option
				if(isset($preset_selections[$identifier_short][$option_identifier]))
				{
					self::header_print_handler($test_profile, $did_print_header);
					$bench_choice = $preset_selections[$identifier_short][$option_identifier];
					echo PHP_EOL . '    Using Pre-Set Run Option: ' . $bench_choice . PHP_EOL;
				}
				else if(isset($preset_selections[$identifier_full][$option_identifier]))
				{
					self::header_print_handler($test_profile, $did_print_header);
					$bench_choice = $preset_selections[$identifier_full][$option_identifier];
					echo PHP_EOL . '    Using Pre-Set Run Option: ' . $bench_choice . PHP_EOL;
				}
				else if($no_prompts)
				{
					$bench_choice = array_keys($option_names);
				}
				else
				{
					$option_names = $o->get_all_option_names_with_messages(true);

					if(count($option_names) > 1)
					{
						$option_names[] = 'Test All Options';
					}
					$o_name = $o->get_name();
					if($o->get_helper_message() != null)
					{
						$o_name .= ' [' . pts_client::cli_just_italic($o->get_helper_message()) . ']';
					}
					if(count($option_names) != 1)
					{
						self::header_print_handler($test_profile, $did_print_header);
					}
					$bench_choice = implode(',', pts_user_io::prompt_text_menu($o_name, $option_names, true, true, pts_client::$display->get_tab() . pts_client::$display->get_tab()));
					if(count($option_names) != 1)
					{
						echo PHP_EOL;
					}
				}

				$bench_choice = $o->parse_selection_choice_input($bench_choice);

				// Format the selected option(s)
				$option_args = array();
				$option_args_description = array();

				foreach($bench_choice as $c)
				{
					$option_args[] = $o->format_option_value_from_select($c);
					$option_args_description[] = $o->format_option_display_from_select($c);
				}

				$text_args[] = $option_args_description;
				$user_args[] = $option_args;
			}
		}

		$test_args = array();
		$test_args_description = array();

		self::compute_all_combinations($test_args, null, $user_args, 0);
		self::compute_all_combinations($test_args_description, null, $text_args, 0, ' - ');

		if(count($test_args) == 1 && isset($test_args[0]) && $test_args[0] == '' && count($test_args_description) == 1 && isset($test_args_description[0]) && $test_args_description[0] == '' && $test_profile->get_test_subtitle() != '')
		{
			// Fill in the descriptuon now so comparison hashes will match sooner than just post-testing
			$test_args_description[0] = $test_profile->get_test_subtitle();
		}

		return array($test_args, $test_args_description);
	}
	public static function default_user_options(&$test_profile)
	{
		// Defaults mode for single test
		$all_args_real = array();
		$all_args_description = array();

		$error_handle = null;
		$option_objects = $test_profile->get_test_option_objects(true, $error_handle);
		if(!empty($error_handle))
		{
			echo PHP_EOL . pts_client::$display->get_tab() . pts_client::cli_just_italic($error_handle) . PHP_EOL;
			return false;
		}
		foreach($option_objects as $o)
		{
			$option_args = array();
			$option_args_description = array();

			$default_entry = $o->get_option_default();

			if($o->option_count() == 2)
			{
				foreach(array(0, 1) as $i)
				{
					$option_args[] = $o->format_option_value_from_select($i);
					$option_args_description[] = $o->format_option_display_from_select($i);
				}
			}
			else
			{
				$option_args[] = $o->format_option_value_from_select($default_entry);
				$option_args_description[] = $o->format_option_display_from_select($default_entry);
			}

			$all_args_real[] = $option_args;
			$all_args_description[] = $option_args_description;
		}

		$test_args = array();
		$test_args_description = array();

		self::compute_all_combinations($test_args, null, $all_args_real, 0);
		self::compute_all_combinations($test_args_description, null, $all_args_description, 0, ' - ');

		return array($test_args, $test_args_description);
	}
	public static function batch_user_options(&$test_profile, $option_select = false, $validate_options_now = true)
	{
		// Batch mode for single test
		$batch_all_args_real = array();
		$batch_all_args_description = array();

		$error_handle = null;
		$option_objects = $test_profile->get_test_option_objects(true, $error_handle, $validate_options_now);
		if(!empty($error_handle))
		{
			echo PHP_EOL . pts_client::$display->get_tab() . pts_client::cli_just_italic($error_handle) . PHP_EOL;
			return false;
		}
		if($option_select != false)
		{
			$os = array();
			foreach(explode(',', $option_select) as $one)
			{
				$one = pts_strings::trim_explode('=', $one);
				if(count($one) != 2)
				{
					continue;
				}
				list($oi, $ov) = $one;

				if(!isset($os[$oi]))
				{
					$os[$oi] = array();
				}

				$os[$oi][] = $ov;
			}
			$option_select = $os;
		}
		foreach($option_objects as $o)
		{
			$option_args = array();
			$option_args_description = array();

			if($option_select != false && isset($option_select[$o->get_identifier()]))
			{
				for($i = 0; $i < $o->option_count(); $i++)
				{
					if(in_array(trim($o->get_option_name($i)), $option_select[$o->get_identifier()]))
					{
						$option_args[] = $o->format_option_value_from_select($i);
						$option_args_description[] = $o->format_option_display_from_select($i);
					}
				}
			}
			else
			{
				for($i = 0; $i < $o->option_count(); $i++)
				{
					$option_args[] = $o->format_option_value_from_select($i);
					$option_args_description[] = $o->format_option_display_from_select($i);
				}
			}

			$batch_all_args_real[] = $option_args;
			$batch_all_args_description[] = $option_args_description;
		}

		$test_args = array();
		$test_args_description = array();

		self::compute_all_combinations($test_args, null, $batch_all_args_real, 0);
		self::compute_all_combinations($test_args_description, null, $batch_all_args_description, 0, ' - ');

		return array($test_args, $test_args_description);
	}
	public static function compute_all_combinations(&$return_arr, $current_string, $options, $counter, $delimiter = ' ')
	{
		// In batch mode, find all possible combinations for test options
		if(count($options) <= $counter)
		{
			$return_arr[] = $current_string != null ? trim($current_string) : '';
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

				self::compute_all_combinations($return_arr, $new_current_string, $options, $counter + 1, $delimiter);
			}
		}
	}
	public static function auto_process_test_option(&$test_profile, $option_identifier, &$option_names, &$option_values, &$option_messages, &$error = null, $validate_config_options = true)
	{
		// Some test items have options that are dynamically built
		switch($option_identifier)
		{
			case 'auto-resolution':
				// Base options off available screen resolutions
				if(count($option_names) == 1 && count($option_values) == 1)
				{
					if(PTS_IS_CLIENT && phodevi::read_property('gpu', 'screen-resolution') && phodevi::read_property('gpu', 'screen-resolution') != array(-1, -1) && !defined('PHOROMATIC_SERVER'))
					{
						$available_video_modes = phodevi::read_property('gpu', 'available-modes');
					}
					else
					{
						$available_video_modes = array();
					}

					if(empty($available_video_modes))
					{
						// Use hard-coded defaults
						$available_video_modes = array(array(800, 600), array(1024, 768), array(1280, 768), array(1280, 960), array(1280, 1024), array(1366, 768),
							array(1400, 1050), array(1600, 900), array(1680, 1050), array(1600, 1200), array(1920, 1080), array(2560, 1600), array(3840, 2160));
					}

					$format_name = $option_names[0];
					$format_value = $option_values[0];
					$option_names = array();
					$option_values = array();

					foreach($available_video_modes as $video_mode)
					{
						$this_name = str_replace('$VIDEO_WIDTH', $video_mode[0], $format_name);
						$this_name = str_replace('$VIDEO_HEIGHT', $video_mode[1], $this_name);

						$this_value = str_replace('$VIDEO_WIDTH', $video_mode[0], $format_value);
						$this_value = str_replace('$VIDEO_HEIGHT', $video_mode[1], $this_value);

						$option_names[] = $this_name;
						$option_values[] = $this_value;
					}
				}
				break;
			case 'auto-resolution-wide':
				// Base options off available screen resolutions (wide format)
				if(count($option_names) == 1 && count($option_values) == 1)
				{
					if(PTS_IS_CLIENT && !defined('PHOROMATIC_SERVER'))
					{
						$current_resolution = phodevi::read_property('gpu', 'screen-resolution');
					}
					else
					{
						$current_resolution = array(3840, 2160);
					}

					$stock_modes = array(
						array(1280, 960),
						array(1600, 1200),
						array(1280, 1024),
						array(1920, 1080),
						array(2560, 1080),
						array(2560, 1440),
						array(2880, 1620),
						array(3840, 1600));
					$available_modes = array();

					for($i = 0; $i < count($stock_modes); $i++)
					{
						if($stock_modes[$i][0] <= $current_resolution[0] && $stock_modes[$i][1] <= $current_resolution[1])
						{
							array_push($available_modes, $stock_modes[$i]);
						}
					}

					$format_name = $option_names[0];
					$format_value = $option_values[0];
					$option_names = array();
					$option_values = array();
					foreach($available_modes as $video_mode)
					{
						$this_name = str_replace('$VIDEO_WIDTH', $video_mode[0], $format_name);
						$this_name = str_replace('$VIDEO_HEIGHT', $video_mode[1], $this_name);

						$this_value = str_replace('$VIDEO_WIDTH', $video_mode[0], $format_value);
						$this_value = str_replace('$VIDEO_HEIGHT', $video_mode[1], $this_value);

						$option_names[] = $this_name;
						$option_values[] = $this_value;
					}
				}
				break;
			case 'auto-disk-partitions':
			case 'auto-disk-mount-points':
				// Base options off available disk partitions
				if(PTS_IS_CLIENT == false)
				{
					//echo 'ERROR: This option is not supported in this configuration.';
					return;
				}

				/*if(phodevi::is_linux())
				{
					$all_devices = array_merge(pts_file_io::glob('/dev/hd*'), pts_file_io::glob('/dev/sd*'));
				}
				else if(phodevi::is_bsd())
				{
					$all_devices = array_merge(pts_file_io::glob('/dev/ad*'), pts_file_io::glob('/dev/ada*'));
				}
				else
				{
					$all_devices = array();
				}*/
				$all_devices = array_merge(pts_file_io::glob('/dev/hd*'), pts_file_io::glob('/dev/sd*'), pts_file_io::glob('/dev/vd*'), pts_file_io::glob('/dev/md*'), pts_file_io::glob('/dev/nvme*'),  pts_file_io::glob('/dev/pmem*'));

				foreach($all_devices as &$device)
				{
					if(!is_numeric(substr($device, -1)))
					{
						unset($device);
					}
				}

				$all_devices = array_merge($all_devices, pts_file_io::glob('/dev/mapper/*'));

				$option_values = array();
				foreach($all_devices as $partition)
				{
					$option_values[] = $partition;
				}

				if($option_identifier == 'auto-disk-mount-points')
				{
					$partitions_d = $option_values;
					$option_values = array();
					$option_names = array();

					$mounts = is_file('/proc/mounts') ? file_get_contents('/proc/mounts') : '';

					$option_values[] = '';
					$option_names[] = 'Default Test Directory';

					if(!empty($mounts))
					{
						foreach($partitions_d as $partition_d)
						{
							$mount_point = substr(($a = substr($mounts, strpos($mounts, $partition_d) + strlen($partition_d) + 1)), 0, strpos($a, ' '));
							if(is_dir($mount_point) && is_writable($mount_point) && !in_array($mount_point, array('/boot', '/boot/efi')) && !in_array($mount_point, $option_values))
							{
								$option_values[] = $mount_point;
								$option_names[] = $mount_point; // ' [' . $partition_d . ']'
							}
						}

						// ZFS only
						$mounts_arr = explode("\n", $mounts);
						foreach($mounts_arr as $mount)
						{
							$mount_arr = explode(' ', $mount);
							if(isset($mount_arr[2]) && $mount_arr[2] == 'zfs')
							{
								$option_values[] = $mount_arr[1];
								$option_names[] = $mount_arr[1];
							}
						}
					}
				}
				else
				{
					$option_names = $option_values;
				}

				break;
			case 'auto-disks':
				// Base options off attached disks
				if(PTS_IS_CLIENT == false)
				{
					//echo 'ERROR: This option is not supported in this configuration.';
					return;
				}

				$all_devices = array_merge(pts_file_io::glob('/dev/hd*'), pts_file_io::glob('/dev/sd*'), pts_file_io::glob('/dev/vd*'), pts_file_io::glob('/dev/md*'), pts_file_io::glob('/dev/nvme*'),  pts_file_io::glob('/dev/pmem*'));

				foreach($all_devices as $i => &$device)
				{
					if(is_numeric(substr($device, -1)) && strpos($device, '/dev/md') === false)
					{
						unset($all_devices[$i]);
					}
				}

				$option_values = array();
				foreach($all_devices as $disk)
				{
					$option_values[] = $disk;
				}
				$option_names = $option_values;
				break;
			case 'auto-removable-media':
				if(PTS_IS_CLIENT == false)
				{
					//echo 'ERROR: This option is not supported in this configuration.';
					return;
				}

				foreach(array_merge(pts_file_io::glob('/media/*/'), pts_file_io::glob('/Volumes/*/')) as $media_check)
				{
					if(is_dir($media_check) && is_writable($media_check)) // add more checks later on
					{
						$option_names[] = $media_check;
						$option_values[] = $media_check;
					}
				}
				break;
			case 'auto-file-select':
				if(PTS_IS_CLIENT == false)
				{
					//echo 'ERROR: This option is not supported in this configuration.';
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
						$option_names[] = $names[$i];
						$option_values[] = $values[$i];
					}
				}
				break;
			case 'auto-executable':
				if(PTS_IS_CLIENT == false)
				{
					//echo 'ERROR: This option is not supported in this configuration.';
					return;
				}

				$names = $option_names;
				$values = $option_values;
				$option_names = array();
				$option_values = array();

				for($i = 0; $i < count($names) && $i < count($values); $i++)
				{
					if(is_executable($values[$i]) || pts_client::executable_in_path($values[$i]))
					{
						$option_names[] = $names[$i];
						$option_values[] = $values[$i];
					}
				}
				break;
			case 'auto-directory-select':
				if(PTS_IS_CLIENT == false)
				{
					//echo 'ERROR: This option is not supported in this configuration.';
					return;
				}

				$names = $option_names;
				$values = $option_values;
				$option_names = array();
				$option_values = array();

				for($i = 0; $i < count($names) && $i < count($values); $i++)
				{
					if(is_dir($values[$i]) && is_writable($values[$i]))
					{
						$option_names[] = $names[$i];
						$option_values[] = $values[$i];
					}
				}
				break;
			case 'cpu-threads':
				if(PTS_IS_CLIENT == false)
				{
					return;
				}

				$option_names = array();
				$option_values = array();

				for($i = 1; $i <= phodevi::read_property('cpu', 'core-count'); $i *= 2)
				{
					$option_names[] = $i;
					$option_values[] = $i;
				}
				if(!in_array(phodevi::read_property('cpu', 'core-count'), $option_names))
				{
					$option_names[] = phodevi::read_property('cpu', 'core-count');
					$option_values[] = phodevi::read_property('cpu', 'core-count');
				}
				break;
			case 'cpu-physical-threads':
				if(PTS_IS_CLIENT == false)
				{
					return;
				}

				$option_names = array();
				$option_values = array();

				for($i = 1; $i <= phodevi::read_property('cpu', 'physical-core-count'); $i *= 2)
				{
					$option_names[] = $i;
					$option_values[] = $i;
				}
				if(!in_array(phodevi::read_property('cpu', 'physical-core-count'), $option_names))
				{
					$option_names[] = phodevi::read_property('cpu', 'physical-core-count');
					$option_values[] = phodevi::read_property('cpu', 'physical-core-count');
				}
				break;
			case 'ram-capacity':
				if(PTS_IS_CLIENT == false)
				{
					return;
				}

				$option_names = array();
				$option_values = array();

				for($i = 32; $i < phodevi::read_property('memory', 'capacity'); $i *= 2)
				{
					if($i >= 1024)
					{
						$pretty = round($i / 1024) . 'GB';
					}
					else
					{
						$pretty = $i . 'MB';
					}
					$option_names[] = $pretty;
					$option_values[] = $i;
				}
				break;
			case 'renderer':
				if(PTS_IS_CLIENT == false)
				{
					return;
				}

				$names = $option_names;
				$values = $option_values;
				$option_names = array();
				$option_values = array();
				$had_valid_fail = false;

				for($i = 0; $i < count($names) && $i < count($values); $i++)
				{
					if($validate_config_options && self::validate_test_arguments_compatibility($names[$i], $test_profile) == false)
					{
						$had_valid_fail = true;
						continue;
					}

					$option_names[] = $names[$i];
					$option_values[] = $values[$i];
				}
				if($had_valid_fail && empty($option_names))
				{
					$error = 'No supported options found for ' . $option_identifier;
					return -1;
				}
				break;
			default:
				if(PTS_IS_CLIENT == false)
				{
					return;
				}

				$names = $option_names;
				$values = $option_values;
				$option_names = array();
				$option_values = array();
				$had_valid_fail = false;

				for($i = 0; $i < count($names) && $i < count($values); $i++)
				{
					if($validate_config_options && self::validate_test_arguments_compatibility($names[$i], $test_profile) == false)
					{
						$had_valid_fail = true;
						continue;
					}

					$option_names[] = $names[$i];
					$option_values[] = $values[$i];
				}
				if($had_valid_fail && empty($option_names))
				{
					$error = 'No supported options found for ' . $option_identifier;
					return -1;
				}
				break;
		}
	}
	public static function validate_test_arguments_compatibility($test_args, &$test_profile, &$error = null)
	{
		if(PTS_IS_CLIENT == false || empty($test_args))
		{
			return true;
		}

		if((stripos($test_args, 'Direct3D') !== false || stripos($test_args, 'D3D') !== false) && phodevi::os_under_test() != 'Windows' && !in_array('wine', $test_profile->get_external_dependencies()))
		{
			// Only show Direct3D renderer options when running on Windows or similar (i.e. Wine)
			$error = 'Direct3D renderer is not supported here.';
			return false;
		}
		if(strpos($test_args, 'Apple ') !== false && phodevi::os_under_test() != 'MacOSX')
		{
			// Only show Apple (namely Metal) renderer options when running on macOS
			$error = 'Apple option is not supported here.';
			return false;
		}
		if((stripos($test_args, 'NVIDIA ') !== false || stripos($test_args . ' ', 'CUDA ') !== false) && stripos(phodevi::read_property('gpu', 'model'), 'NVIDIA') === false)
		{
			// Only show NVIDIA / CUDA options when running with NVIDIA hardware
			$error = 'NVIDIA CUDA support is not available.';
			return false;
		}
		if((stripos($test_args, 'NVIDIA ') !== false || stripos($test_args . ' ', 'CUDA ') !== false) && stripos(phodevi::read_property('gpu', 'model'), 'NVIDIA') === false)
		{
			// Only show NVIDIA / CUDA options when running with NVIDIA hardware
			$error = 'NVIDIA support is not available.';
			return false;
		}
		if((stripos($test_args, 'Radeon ') !== false || stripos($test_args . ' ', 'ROCm ') !== false) && stripos(phodevi::read_property('gpu', 'model'), 'AMD') === false && stripos(phodevi::read_property('gpu', 'model'), 'Radeon') === false)
		{
			// Only show Radeon GPU options with AMD GPUs / ROCm (such as Blender's HIP support)
			$error = 'AMD Radeon support is not available.';
			return false;
		}
		if(stripos($test_args, 'OpenCL') !== false && phodevi::opencl_support_detected() === false)
		{
			// Try to only show OpenCL configurations if known to be working
			$error = 'OpenCL support seems to be unavailable.';
			return false;
		}
		if(stripos($test_args, 'Vulkan') !== false && phodevi::vulkan_support_detected() === false)
		{
			// Try to only show Vulkan configurations if known to be working
			$error = 'Vulkan support seems to be unavailable.';
			return false;
		}
		if(stripos($test_args, 'Windows') !== false && !phodevi::is_windows())
		{
			// Do not show options mentioning Windows if not on Windows
			$error = 'Windows option is not available.';
			return false;
		}
		if(stripos($test_args, 'Linux') !== false && !phodevi::is_linux())
		{
			// Do not show options mentioning Linux if not on Linux
			$error = 'Linux support is not available.';
			return false;
		}
		if(stripos($test_args, 'macOS ') !== false && !phodevi::is_macos())
		{
			// Do not show options mentioning macOS if not on macOS
			$error = 'Apple macOS support is not available.';
			return false;
		}
		if(stripos($test_args, 'x86_64') !== false && phodevi::read_property('system', 'kernel-architecture') != 'x86_64')
		{
			$error = 'x86_64 option unavailable for non-x86_64 architecture.';
			return false;
		}

		return true;
	}
}

?>
