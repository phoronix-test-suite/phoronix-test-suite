<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel

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

class gpu_temp implements phodevi_sensor
{
	public static function get_type()
	{
		return 'gpu';
	}
	public static function get_sensor()
	{
		return 'temp';
	}
	public static function get_unit()
	{
		return 'Celsius';
	}
	public static function support_check()
	{
		$test = self::read_sensor();
		return is_numeric($test) && $test != -1;
	}
	public static function read_sensor()
	{
		// Report graphics processor temperature
		$temp_c = -1;

		if(IS_NVIDIA_GRAPHICS)
		{
			$temp_c = phodevi_parser::read_nvidia_extension('GPUCoreTemp');
		}
		else if(IS_ATI_GRAPHICS && IS_LINUX)
		{
			$temp_c = phodevi_linux_parser::read_ati_overdrive('Temperature');
		}
		else
		{
			foreach(pts_file_io::glob('/sys/class/drm/card0/device/hwmon/hwmon*/temp1_input') as $temp_input)
			{
				// This works for at least Nouveau driver with Linux 2.6.37 era DRM
				$temp_input = pts_file_io::file_get_contents($temp_input);

				if(is_numeric($temp_input))
				{
					$temp_c = $temp_input;
					break;
				}
			}
		}

		return $temp_c;
	}
}

?>
