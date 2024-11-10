<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2021, Phoronix Media
	Copyright (C) 2009 - 2021, Michael Larabel

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
	protected static $amd_energy_sockets = false;
	protected static $cpu_power_inputs = false;

	public function read_sensor()
	{
		if(phodevi::is_linux())
		{
			return $this->cpu_power_linux();
		}
		else if(phodevi::is_macos())
		{
			return self::read_macosx_power_metrics();
		}
		return -1;		// TODO make -1 a named constant
	}
	public static function get_unit()
	{
		$unit = null;

		if(phodevi::is_linux() && is_readable('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power1_input'))
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

		if(self::$amd_energy_sockets === false)
		{
			self::$amd_energy_sockets = array();
			foreach(pts_file_io::glob('/sys/class/hwmon/hwmon*/name') as $hwmon)
			{
				if(pts_file_io::file_get_contents($hwmon) == 'amd_energy')
				{
					$hwmon_dir = dirname($hwmon);

					foreach(glob($hwmon_dir . '/energy*_label') as $label)
					{
						if(strpos(file_get_contents($label), 'Esocket') !== false)
						{
							self::$amd_energy_sockets[] = str_replace('_label', '_input', $label);
						}
					}
					break;
				}
			}
		}
		if(self::$cpu_power_inputs === false)
		{
			self::$cpu_power_inputs = array();
			foreach(pts_file_io::glob('/sys/class/hwmon/hwmon*/power*_label') as $hwmon)
			{
				if(in_array(pts_file_io::file_get_contents($hwmon), array('CPU power', 'IO power')))
				{
					$hwmon = str_replace('_label', '_input', $hwmon);

					if(pts_file_io::file_get_contents($hwmon) > 0)
					{
						self::$cpu_power_inputs[] = $hwmon;
					}
				}
			}
		}

		if(is_readable('/sys/bus/i2c/drivers/ina3221x/0-0041/iio:device1/in_power1_input'))
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
					$domain_name = pts_file_io::file_get_contents($rapl_base_path_1 . '/name');
					if (strncmp($domain_name, "psys", 4) === 0)
					{
						// Ignore psys domain
						continue;
					}

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
		else if(!empty(self::$amd_energy_sockets))
		{
			$tries = 0;
			do
			{
				$tries++;
				$j1 = 0;
				$j2 = 0;
				foreach(self::$amd_energy_sockets as $f)
				{
					$j1 += trim(file_get_contents($f));
				}
				sleep(1);
				foreach(self::$amd_energy_sockets as $f)
				{
					$j2 += trim(file_get_contents($f));
				}
				$cpu_power = ($j2 - $j1) * 0.0000010;

				// This loop is in case the counters roll over
			}
			while($cpu_power < 1 && $tries < 2);
		}
		else if(!empty(self::$cpu_power_inputs))
		{
			// APM XGene / Ampere Computing
			$cpu_uwatts = 0;
			foreach(self::$cpu_power_inputs as $power_input)
			{
				$pi = pts_file_io::file_get_contents($power_input);

				if(is_numeric($pi))
				{
					$cpu_uwatts += $pi;
				}
			}
			$cpu_power = $cpu_uwatts / 1000000;
		}
		else if(is_readable('/sys/class/hwmon/hwmon0/name') && pts_file_io::file_get_contents('/sys/class/hwmon/hwmon0/name') == 'zenpower')
		{
			foreach(pts_file_io::glob('/sys/class/hwmon/hwmon*/power*_label') as $label)
			{
				if(pts_file_io::file_get_contents($label) == 'SVI2_P_SoC')
				{
					$cpu_power += pts_file_io::file_get_contents(str_replace('_label', '_input', $label));
				}
			}
			if($cpu_power > 100000)
			{
				$cpu_power = $cpu_power / 100000;
			}
		}
		else if(($power_oem_info = pts_file_io::glob('/sys/class/hwmon/hwmon*/device/power1_oem_info')) && !empty($power_oem_info))
		{
			// Grace https://docs.nvidia.com/grace-performance-tuning-guide.pdf
			foreach($power_oem_info as $info_file)
			{
				if(stripos(pts_file_io::file_get_contents($info_file), 'CPU Power') !== false)
				{
					$bdir = dirname($info_file);
					if(is_file($bdir . '/power1_average'))
					{
						$this_power = pts_file_io::file_get_contents($bdir . '/power1_average');
						if(is_numeric($this_power) && $this_power > 1000000)
						{
							$cpu_power += round($this_power / 1000000, 2);
						}
					}
				}
			}
		}

		return round($cpu_power, 2);
	}
	public static function read_macosx_power_metrics()
	{
		$watts = 0;
		if(pts_client::executable_in_path('powermetrics'))
		{
            // Unfortunately needs sudo so for most uses /etc/sudoers :
            // username ALL = (ALL) NOPASSWD: /usr/bin/powermetrics
			$powermetrics = shell_exec("sudo -n powermetrics -n 1 -i 1 --samplers cpu_power 2>&1");
            if(($x = strpos($powermetrics, 'Combined Power ')) !== false)
            {
                $powermetrics = substr($powermetrics, $x);
                $powermetrics = substr($powermetrics, strpos($powermetrics, ': ') + 2);
                if(($x = strpos($powermetrics, ' mW')) !== false)
                {
                    $powermetrics = substr($powermetrics, 0, $x);

                    if(is_numeric($powermetrics) && $powermetrics > 0)
                    {
                        $watts = $powermetrics / 1000;
                    }
                }
            }
            else if(($x = strpos($powermetrics, 'Package Power: ')) !== false)
            {
                $powermetrics = substr($powermetrics, $x + strlen('Package Power: '));
                if(($x = strpos($powermetrics, ' mW')) !== false)
                {
                    $powermetrics = substr($powermetrics, 0, $x);

                    if(is_numeric($powermetrics) && $powermetrics > 0)
                    {
                        $watts = $powermetrics / 1000;
                    }
                }
            }
		}

		return $watts;
	}
}

?>
