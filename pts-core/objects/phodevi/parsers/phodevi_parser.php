<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	phodevi_parser.php: General parsing functions used by different parts of Phodevi that are supported by more than one OS

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

class phodevi_parser
{
	public static function read_nvidia_extension($attribute)
	{
		// Read NVIDIA's NV Extension
		$nv_info = false;

		if(pts_executable_in_path("nvidia-settings"))
		{
			$info = shell_exec("nvidia-settings --query " . $attribute . " 2>&1");

			if(($pos = strpos($info, pts_last_string_in_string($attribute, '/'))) > 0 && strpos($info, "ERROR:") === false)
			{
				$nv_info = substr($info, strpos($info, "):") + 3);
				$nv_info = substr($nv_info, 0, strpos($nv_info, "\n"));
				$nv_info = trim(substr($nv_info, 0, strrpos($nv_info, ".")));
			}
		}

		return $nv_info;
	}
	public static function read_xdpy_monitor_info()
	{
		// Read xdpyinfo monitor information
		$monitor_info = array();

		if(pts_executable_in_path("xdpyinfo"))
		{
			$info = trim(shell_exec("xdpyinfo -ext XINERAMA 2>&1 | grep head"));

			foreach(explode("\n", $info) as $xdpyinfo_line)
			{
				if(!empty($xdpyinfo_line) && strpos($xdpyinfo_line, "0x0") == false)
				{
					array_push($monitor_info, $xdpyinfo_line);
				}
			}
		}

		return $monitor_info;
	}
	public static function read_hddtemp($disk = null)
	{
		// Read hard drive temperature using hddtemp
		$hdd_temperature = -1;

		if(pts_executable_in_path("hddtemp"))
		{
			if(empty($disk))
			{
				$disks = glob("/dev/sd*");

				if(count($disks) > 0)
				{
					$disk = array_shift($disks);
				}
			}

			// For most situations this won't work since hddtemp usually requires root access
			$info = trim(shell_exec("hddtemp " . $disk . " 2>&1"));

			if(($start_pos = strrpos($info, ": ")) > 0 && ($end_pos = strrpos($info, "°")) > $start_pos)
			{
				$temperature = substr($info, ($start_pos + 2), ($end_pos - $start_pos - 2));

				if(is_numeric($temperature))
				{
					$unit = substr($info, $end_pos + 2, 1);
					if($unit == "F")
					{
						$temperature = pts_trim_double((($temperature - 32) * 5 / 9));
					}

					$hdd_temperature = $temperature;
				}
			}
		}

		return $hdd_temperature;
	}
	public static function read_xorg_module_version($module)
	{
		$module_version = false;
		if(is_file("/var/log/Xorg.0.log"))
		{
			$xorg_log = file_get_contents("/var/log/Xorg.0.log");

			if(($module_start = strpos($xorg_log, $module)) > 0)
			{
				$xorg_log = substr($xorg_log, $module_start);
				$temp_version = substr($xorg_log, strpos($xorg_log, "module version =") + 17);
				$temp_version = substr($temp_version, 0, strpos($temp_version, "\n"));

				if(is_numeric(str_replace(".", "", $temp_version)))
				{
					$module_version = $temp_version;
				}
			}
		}

		return $module_version;
	}
	public static function parse_equal_delimited_file($file, $key)
	{
		$return_value = false;

		foreach(explode("\n", pts_file_get_contents($file)) as $build_line)
		{
			list($descriptor, $value) = pts_trim_explode("=", $build_line);

			if($descriptor == $key)
			{
				$return_value = $value;
				break;
			}
		}

		return $return_value;
	}
	public static function hardware_values_to_remove()
	{
		static $remove_words = null;

		if($remove_words == null && is_file(STATIC_DIR . "lists/hal-values-remove.list"))
		{
			$word_file = pts_file_get_contents(STATIC_DIR . "lists/hal-values-remove.list");
			$remove_words = pts_trim_explode("\n", $word_file);
		}

		return $remove_words;
	}
	public static function software_glxinfo_version()
	{
		static $info = -1;

		if($info == -1)
		{
			$info = null;
			$glxinfo = shell_exec("glxinfo 2> /dev/null");

			if(($pos = strpos($glxinfo, "OpenGL version string:")) !== false)
			{
				$info = substr($glxinfo, $pos + 23);
				$info = substr($info, 0, strpos($info, "\n"));
				$info = trim(str_replace(array(" Release"), "", $info));
			}
		}

		return $info;
	}
}

?>
