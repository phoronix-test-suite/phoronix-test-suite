<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2018, Phoronix Media
	Copyright (C) 2009 - 2018, Michael Larabel

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

class gpu_power extends phodevi_sensor
{
	const SENSOR_TYPE = 'gpu';
	const SENSOR_SENSES = 'power';
	private static $unit = 'Milliwatts';

	public static function get_unit()
	{
		return self::$unit;
	}
	public function read_sensor()
	{
		$gpu_power = -1;

		if(($nvidia_smi = pts_client::executable_in_path('nvidia-smi')))
		{
			$smi_output = shell_exec(escapeshellarg($nvidia_smi) . ' -q -d POWER');
			$power = strpos($smi_output, 'Power Draw');
			if($power !== false)
			{
				$power = substr($smi_output, strpos($smi_output, ':', $power) + 1);
				$power = trim(substr($power, 0, strpos($power, 'W')));

				if(is_numeric($power) && $power > 0)
				{
					self::$unit = 'Watts';
					$gpu_power = $power;
				}
			}

		}
		else if($power1_average = phodevi_linux_parser::read_sysfs_node('/sys/class/drm/card0/device/hwmon/hwmon*/power1_average', 'POSITIVE_NUMERIC'))
		{
			// AMDGPU path
			if(is_numeric($power1_average))
			{
				$power1_average = $power1_average / 1000000;
				if($power1_average > 1 && $power1_average < 600)
				{
					self::$unit = 'Watts';
					$gpu_power = $power1_average;
				}
			}
		}
		else if(is_readable('/sys/kernel/debug/dri/0/i915_emon_status'))
		{
			$i915_emon_status = file_get_contents('/sys/kernel/debug/dri/0/i915_emon_status');
			$power = strpos($i915_emon_status, 'Total power: ');

			if($power !== false)
			{
				$power = substr($i915_emon_status, $power + 13);
				$power = substr($power, 0, strpos($power, PHP_EOL));

				if(is_numeric($power))
				{
					if($power > 10000000)
					{
						$power /= 1000;
					}

					$gpu_power = $power;
				}
			}
		}
		else if(is_readable('/sys/bus/i2c/drivers/ina3221x/0-0040/iio:device0/in_power0_input'))
		{
			$in_power0_input = pts_file_io::file_get_contents('/sys/bus/i2c/drivers/ina3221x/0-0040/iio:device0/in_power0_input');
			if(is_numeric($in_power0_input) && $in_power0_input > 1)
			{
				$gpu_power = $in_power0_input;
			}
		}

		return $gpu_power;
	}

}

?>
