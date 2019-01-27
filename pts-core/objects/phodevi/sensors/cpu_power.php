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

class cpu_power extends phodevi_sensor
{
	const SENSOR_TYPE = 'cpu';
	const SENSOR_SENSES = 'power';
	static $cpu_energy = 0;
	static $last_time = 0;

	public function read_sensor()
	{
		if(phodevi::is_linux())
		{
			return $this->cpu_power_linux();
		}
		return -1;		// TODO make -1 a named constant
	}
	public static function get_unit()
	{
		$unit = null;

		if(is_readable('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power1_input'))
		{
			$unit = 'Milliwatts';
		}
		else
		{
			$unit = 'Watts';
		}

		return $unit;
	}

	private function cpu_power_linux()
	{
		$cpu_power = -1;

		// Try hwmon interface for AMD 15h (Bulldozer FX CPUs) where this support was introduced for AMD CPUs and exposed by the fam15h_power hwmon driver
		// The fam15h_power driver doesn't expose the power consumption on a per-core/per-package basis but only an average
		$hwmon_watts = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/device/power1_input', 'POSITIVE_NUMERIC', array('name' => 'fam15h_power'));

		if($hwmon_watts != -1)
		{
			if($hwmon_watts > 1000000)
			{
				// convert to Watts
				$hwmon_watts = $hwmon_watts / 1000000;
			}

			$cpu_power = pts_math::set_precision($hwmon_watts, 2);
		}
		else if(is_readable('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power1_input'))
		{
			$in_power1_input = pts_file_io::file_get_contents('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power1_input');
			if(is_numeric($in_power1_input) && $in_power1_input > 1)
			{
				$cpu_power = $in_power1_input;
			}
		}
		else if(is_readable('/sys/class/powercap/intel-rapl/intel-rapl:0/energy_uj'))
		{
			$rapl_base_path = "/sys/class/powercap/intel-rapl/intel-rapl:";
			$total_energy = 0;
			for($x = 0; $x <= 128; $x++)
			{
				$rapl_base_path_1 = $rapl_base_path . $x;
				if(is_readable($rapl_base_path_1))
				{
					$energy_uj = pts_file_io::file_get_contents($rapl_base_path_1 . '/energy_uj');
					if(is_numeric($energy_uj))
					{
						$total_energy += $energy_uj;
					}
				}
				else
				{
					break;
				}
			}

			if($total_energy > 1)
			{
				if(self::$cpu_energy == 0)
				{
					self::$cpu_energy = $total_energy;
					self::$last_time = time();
					$cpu_power = 0;
				}
				else
				{
					$cpu_power = ($total_energy - self::$cpu_energy) / (time() - self::$last_time) / 1000000;
				}
				self::$last_time = time();
				self::$cpu_energy = $total_energy;
			}
		}

		return round($cpu_power, 2);
	}
}

?>
