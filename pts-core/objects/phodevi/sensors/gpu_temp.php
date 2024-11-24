<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2019, Phoronix Media
	Copyright (C) 2009 - 2019, Michael Larabel

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

class gpu_temp extends phodevi_sensor
{
	const SENSOR_TYPE = 'gpu';
	const SENSOR_SENSES = 'temp';
	const SENSOR_UNIT = 'Celsius';

	public function read_sensor()
	{
		// Report graphics processor temperature
		$temp_c = -1;

		if(phodevi::is_nvidia_graphics())
		{
			$temp_c = phodevi_parser::read_nvidia_extension('GPUCoreTemp');
		}

		if($temp_c == -1 || empty($temp_c))
		{
			foreach(array_merge(array('/sys/class/drm/card0/device/temp1_input'), pts_file_io::glob('/sys/class/drm/card*/device/hwmon/hwmon*/temp1_input')) as $temp_input)
			{
				// This works for at least Nouveau driver with Linux 2.6.37 era DRM
				if(is_readable($temp_input) == false)
				{
					continue;
				}

				$temp_input = pts_file_io::file_get_contents($temp_input);

				if(is_numeric($temp_input))
				{
					if($temp_input > 1000)
					{
						$temp_input /= 1000;
					}

					$temp_c = $temp_input;
					break;
				}
			}

			if($temp_c == -1 && is_readable('/sys/kernel/debug/dri/0/i915_emon_status'))
			{
				// Intel thermal
				$i915_emon_status = file_get_contents('/sys/kernel/debug/dri/0/i915_emon_status');
				$temp = strpos($i915_emon_status, 'GMCH temp: ');

				if($temp !== false)
				{
					$temp = substr($i915_emon_status, $temp + 11);
					$temp = substr($temp, 0, strpos($temp, PHP_EOL));

					if(is_numeric($temp) && $temp > 0)
					{
						$temp_c = $temp;
					}
				}
			}

			if($temp_c == -1)
			{
				foreach(pts_file_io::glob('/sys/class/hwmon/hwmon*/name') as $temp_name)
				{
					// This works on the NVIDIA Jetson TX1
					// On the TX1 the name = GPU-therm
					if(is_readable($temp_name) == false || stripos(file_get_contents($temp_name), 'GPU') === false)
					{
						continue;
					}

					$temp_input_file = dirname($temp_name) . '/temp1_input';

					if(!is_file($temp_input_file))
					{
						continue;
					}

					$temp_input = pts_file_io::file_get_contents($temp_input_file);

					if(is_numeric($temp_input))
					{
						if($temp_input > 1000)
						{
							$temp_input /= 1000;
						}

						$temp_c = $temp_input;
						break;
					}
				}
			}
		}
		if($temp_c == -1)
		{
			// Try ACPI thermal
			$temp_c = phodevi_linux_parser::read_sysfs_node('/sys/class/thermal/thermal_zone*/temp', 'POSITIVE_NUMERIC', array('type' => 'gpu_thermal'));
			if(is_numeric($temp_c) && $temp_c > 1000)
			{
				$temp_c /= 1000;
			}
		}

		if($temp_c == -1)
		{
			// Try ACPI thermal (Tegra works here)
			$temp_c = phodevi_linux_parser::read_sysfs_node('/sys/class/thermal/thermal_zone*/temp', 'POSITIVE_NUMERIC', array('type' => 'GPU-therm'));
			if(is_numeric($temp_c) && $temp_c > 1000)
			{
				$temp_c /= 1000;
			}
		}

		if($temp_c > 1000 || $temp_c < 9)
		{
			// Invalid data
			return -1;
		}
		
		return pts_math::set_precision($temp_c, 2);
	}

}

?>
