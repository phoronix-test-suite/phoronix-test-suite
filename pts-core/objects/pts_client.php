<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class pts_client
{
	public static function terminal_width()
	{
		if(!pts_is_assignment("TERMINAL_WIDTH"))
		{
			$chars = -1;

			if(pts_executable_in_path("tput"))
			{
				$terminal_width = trim(shell_exec("tput cols 2>&1"));

				if(is_numeric($terminal_width) && $terminal_width > 1)
				{
					$chars = $terminal_width;
				}
			}

			pts_set_assignment("TERMINAL_WIDTH", $chars);
		}

		return pts_read_assignment("TERMINAL_WIDTH");
	}
	public static function user_hardware_software_reporting()
	{
		$hw_reporting = pts_string_bool(pts_config::read_user_config(P_OPTION_HARDWARE_REPORTING, 0));
		$sw_reporting = pts_string_bool(pts_config::read_user_config(P_OPTION_SOFTWARE_REPORTING, 0));

		if($hw_reporting == false && $sw_reporting == false)
		{
			return;
		}

		$hw = array();
		$sw = array();
		$pso = pts_storage_object::recover_from_file(PTS_CORE_STORAGE);

		if($hw_reporting)
		{
			$hw = array(
			"cpu" => phodevi::read_property("cpu", "model"),
			"cpu_count" => phodevi::read_property("cpu", "core-count"),
			"cpu_speed" => phodevi::read_property("cpu", "default-frequency") * 1000,
			"chipset" => phodevi::read_name("chipset"),
			"motherboard" => phodevi::read_name("motherboard"),
			"gpu" => phodevi::read_property("gpu", "model")
			);
			$hw_prev = $pso->read_object("global_reported_hw");
			$pso->add_object("global_reported_hw", $hw); 

			if(is_array($hw_prev))
			{
				$hw = array_diff_assoc($hw, $hw_prev);
			}
		}
		if($sw_reporting)
		{
			$sw = array(
			"os" => phodevi::read_property("system", "operating-system"),
			"os_architecture" => phodevi::read_property("system", "kernel-architecture"),
			"display_server" => phodevi::read_property("system", "display-server"),
			"display_driver" => pts_first_string_in_string(phodevi::read_property("system", "display-driver-string")),
			"desktop" => pts_first_string_in_string(phodevi::read_property("system", "desktop-environment")),
			"compiler" => phodevi::read_property("system", "compiler"),
			"file_system" => phodevi::read_property("system", "filesystem"),
			"screen_resolution" => phodevi::read_property("gpu", "screen-resolution-string")
			);
			$sw_prev = $pso->read_object("global_reported_hw");
			$pso->add_object("global_reported_sw", $sw); 

			if(is_array($sw_prev))
			{
				$sw = array_diff_assoc($sw, $sw_prev);
			}
		}

		$to_report = array_merge($hw, $sw);
		$pso->save_to_file(PTS_CORE_STORAGE);

		if(!empty($to_report))
		{
			pts_global_upload_hwsw_data($to_report);
		}				
	}
	public static function parse_value_string_double_identifier($value_string)
	{
		// i.e. with PRESET_OPTIONS="stream.run-type=Add"
		$values = array();

		foreach(explode(';', $value_string) as $preset)
		{
			if(count($preset = pts_trim_explode('=', $preset)) == 2)
			{
				if(count($preset[0] = pts_trim_explode('.', $preset[0])) == 2)
				{
					$values[$preset[0][0]][$preset[0][1]] = $preset[1];
				}
			}
		}

		return $values;
	}
	public static function create_temporary_file()
	{
		return tempnam(pts_client::temporary_directory(), 'PTS');
	}
	public static function temporary_directory()
	{
		if(PHP_VERSION_ID >= 50210)
		{
			$dir = sys_get_temp_dir();
		}
		else
		{
			$dir = "/tmp"; // Assume /tmp
		}

		return $dir;
	}
	public static function read_env($var)
	{
		static $vars = null;

		if(!isset($vars[$var]))
		{
			$vars[$var] = getenv($var);
		}

		return $vars[$var];
	}
}

?>
