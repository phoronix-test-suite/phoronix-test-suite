<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel
	phodevi_cpu.php: The PTS Device Interface object for the CPU / processor

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

class phodevi_cpu extends phodevi_device_interface
{
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case 'identifier':
				$property = new phodevi_device_property('cpu_string', phodevi::smart_caching);
				break;
			case 'model':
				$property = new phodevi_device_property('cpu_model', phodevi::smart_caching);
				break;
			case 'mhz-default-frequency':
				$property = new phodevi_device_property('cpu_default_frequency_mhz', phodevi::smart_caching);
				break;
			case 'default-frequency':
				$property = new phodevi_device_property(array('cpu_default_frequency', 0), phodevi::smart_caching);
				break;
			case 'core-count':
				$property = new phodevi_device_property('cpu_core_count', phodevi::smart_caching);
				break;
		}

		return $property;
	}
	public static function cpu_string()
	{
		$model = phodevi::read_property('cpu', 'model');

		// Append the processor frequency to string
		if(($freq = phodevi::read_property('cpu', 'default-frequency')) > 0)
		{
			$model = str_replace($freq . 'GHz', null, $model); // we'll replace it if it's already in the string
			$model .= ' @ ' . $freq . 'GHz';
		}

		$core_count = phodevi::read_property('cpu', 'core-count');

		return $model . ' (' . $core_count . ' Core' . ($core_count > 1 ? 's' : null) . ')';
	}
	public static function cpu_core_count()
	{
		$info = null;

		if(phodevi::is_linux())
		{
			if(is_file('/sys/devices/system/cpu/present'))
			{
				$present = pts_file_io::file_get_contents('/sys/devices/system/cpu/present');

				if(substr($present, 0, 2) == '0-')
				{
					$present = substr($present, 2);

					if(is_numeric($present))
					{
						$info = $present + 1;
					}
				}
			}

			if($info == null)
			{
				$info = count(phodevi_linux_parser::read_cpuinfo('processor'));
			}
		}
		else if(phodevi::is_solaris())
		{
			$info = count(explode(PHP_EOL, trim(shell_exec('psrinfo'))));
		}
		else if(phodevi::is_bsd())
		{
			$info = intval(phodevi_bsd_parser::read_sysctl('hw.ncpu'));
		}
		else if(phodevi::is_macosx())
		{
			$info = phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'TotalNumberOfCores');
		}
		else if(phodevi::is_windows())
		{
			$info = getenv('NUMBER_OF_PROCESSORS');
		}

		return (is_numeric($info) && $info > 0 ? $info : 1);
	}
	public static function cpu_default_frequency_mhz()
	{
		return self::cpu_default_frequency() * 1000;
	}
	public static function cpu_default_frequency($cpu_core = 0)
	{
		// Find out the processor frequency

		if(phodevi::is_linux())
		{
			// First, the ideal way, with modern CPUs using CnQ or EIST and cpuinfo reporting the current
			if(is_file('/sys/devices/system/cpu/cpu' . $cpu_core . '/cpufreq/scaling_max_freq'))
			{
				$info = pts_file_io::file_get_contents('/sys/devices/system/cpu/cpu' . $cpu_core . '/cpufreq/scaling_max_freq');
				$info = intval($info) / 1000000;
			}
			else if(is_file('/proc/cpuinfo')) // fall back for those without cpufreq
			{
				$cpu_speeds = phodevi_linux_parser::read_cpuinfo('cpu MHz');
				$cpu_core = isset($cpu_speeds[$cpu_core]) ? $cpu_core : 0;
				$info = isset($cpu_speeds[$cpu_core]) ? ($cpu_speeds[$cpu_core] / 1000) : 0;
			}
		}
		else if(phodevi::is_bsd())
		{
			$info = phodevi_bsd_parser::read_sysctl(array('hw.acpi.cpu.px_global', 'machdep.est.frequency.target'));

			if(is_numeric($info))
			{
				$info = $info / 1000;
			}
			else
			{
				$info = null;
			}
		}
		else if(phodevi::is_windows())
		{
			$info = phodevi_windows_parser::read_cpuz('Processor 1', 'Stock frequency');
			if($info != null)
			{
				if(($e = strpos($info, ' MHz')) !== false)
				{
					$info = substr($info, 0, $e);
				}

				$info = $info / 1000;
			}
		}
		else
		{
			$info = phodevi::read_sensor(array('cpu', 'freq'));

			if($info > 1000)
			{
				// Convert from MHz to GHz
				$info = $info / 1000;
			}
		}

		return pts_math::set_precision($info, 2);
	}
	public static function cpu_model()
	{
		// Returns the processor name / frequency information
		$info = null;

		if(phodevi::is_linux())
		{
			$physical_cpu_ids = phodevi_linux_parser::read_cpuinfo('physical id');
			$physical_cpu_count = count(array_unique($physical_cpu_ids));

			$cpu_strings = phodevi_linux_parser::read_cpuinfo(array('model name', 'Processor'));
			$cpu_strings_unique = array_unique($cpu_strings);

			if($physical_cpu_count == 1 || empty($physical_cpu_count))
			{
				// Just one processor
				if(($cut = strpos($cpu_strings[0], ' (')) !== false)
				{
					$cpu_strings[0] = substr($cpu_strings[0], 0, $cut);
				}

				$info = $cpu_strings[0];
			}
			else if($physical_cpu_count > 1 && count($cpu_strings_unique) == 1)
			{
				// Multiple processors, same model
				$info = $physical_cpu_count . ' x ' . $cpu_strings[0];
			}
			else if($physical_cpu_count > 1 && count($cpu_strings_unique) > 1)
			{
				// Multiple processors, different models
				$current_id = -1;
				$current_string = $cpu_strings[0];
				$current_count = 0;
				$cpus = array();

				for($i = 0; $i < count($physical_cpu_ids); $i++)
				{
					if($current_string != $cpu_strings[$i] || $i == (count($physical_cpu_ids) - 1))
					{
						array_push($cpus, $current_count . ' x ' . $current_string);

						$current_string = $cpu_strings[$i];
						$current_count = 0;
					}

					if($physical_cpu_ids[$i] != $current_id)
					{
						$current_count++;
						$current_id = $physical_cpu_ids[$i];
					}
				}
				$info = implode(', ', $cpus);
			}
		}
		else if(phodevi::is_solaris())
		{
			$dmi_cpu = phodevi_solaris_parser::read_sun_ddu_dmi_info('CPUType', '-C');

			if(count($dmi_cpu) == 0)
			{
				$dmi_cpu = phodevi_solaris_parser::read_sun_ddu_dmi_info('ProcessorName');
			}

			if(count($dmi_cpu) > 0)
			{
				$info = $dmi_cpu[0];
			}
			else
			{
				$info = trim(shell_exec('dmesg 2>&1 | grep cpu0'));
				$info = trim(substr($info, strrpos($info, 'cpu0:') + 6));

				if(empty($info))
				{
					$info = array_pop(phodevi_solaris_parser::read_sun_ddu_dmi_info('ProcessorManufacturer'));
				}
			}

			//TODO: Add in proper support for reading multiple CPUs, similar to the code from above
			$physical_cpu_count = count(phodevi_solaris_parser::read_sun_ddu_dmi_info('ProcessorSocketType'));
			if($physical_cpu_count > 1 && !empty($info))
			{
				// TODO: For now assuming when multiple CPUs are installed, that they are of the same type
				$info = $physical_cpu_count . ' x ' . $info;
			}
		}
		else if(phodevi::is_bsd())
		{
			$info = phodevi_bsd_parser::read_sysctl('hw.model');
		}
		else if(phodevi::is_macosx())
		{
			$info = phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'ProcessorName');
		}
		else if(phodevi::is_windows())
		{
			$info = phodevi_windows_parser::read_cpuz('Processor 1', 'Name');

			if(!$info)
			{
				$info = getenv('PROCESSOR_IDENTIFIER');
			}
		}

		if(empty($info))
		{
			$info = 'Unknown';
		}

		if(($strip_point = strpos($info, '@')) > 0)
		{
			$info = trim(substr($info, 0, $strip_point)); // stripping out the reported freq, since the CPU could be overclocked, etc
		}

		return $info;
	}
}

?>
