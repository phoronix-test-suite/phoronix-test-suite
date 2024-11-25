<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2024, Phoronix Media
	Copyright (C) 2008 - 2024, Michael Larabel
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
	// TODO XXX: $cpuinfo is now useless and needs to be replaced by the VFS layer... update OpenBenchmarking.org accordingly
	public static $cpuinfo = false;
	private static $cpu_flags = -1;

	public static function properties()
	{
		return array(
			'identifier' => new phodevi_device_property('cpu_string', phodevi::smart_caching),
			'model' => new phodevi_device_property('cpu_model', phodevi::smart_caching),
			'model-and-speed' => new phodevi_device_property('cpu_model_and_speed', phodevi::smart_caching),
			'mhz-default-frequency' => new phodevi_device_property('cpu_default_frequency_mhz', phodevi::smart_caching),
			'default-frequency' => new phodevi_device_property(array('cpu_default_frequency', 0), phodevi::smart_caching),
			'core-count' => new phodevi_device_property('cpu_core_count', phodevi::std_caching),
			'physical-core-count' => new phodevi_device_property('cpu_physical_core_count', phodevi::std_caching),
			'thread-count' => new phodevi_device_property('cpu_thread_count', phodevi::std_caching),
			'node-count' => new phodevi_device_property('cpu_node_count', phodevi::smart_caching),
			'scaling-governor' => new phodevi_device_property('cpu_scaling_governor', phodevi::std_caching),
			'power-management' => new phodevi_device_property('cpu_power_management', phodevi::std_caching),
			'microcode-version' => new phodevi_device_property('cpu_microcode_version', phodevi::std_caching),
			'core-family-name' => new phodevi_device_property('get_core_name', phodevi::smart_caching),
			'cache-size' => new phodevi_device_property('cpu_cache_size', phodevi::smart_caching),
			'cache-size-string' => new phodevi_device_property('cpu_cache_size_string', phodevi::smart_caching),
			'smt' => new phodevi_device_property('cpu_smt', phodevi::std_caching),
			'cpu-family' => new phodevi_device_property('get_cpu_family', phodevi::smart_caching),
			'cpu-model' => new phodevi_device_property('get_cpu_model', phodevi::smart_caching),
			);
	}
	public static function cpu_string()
	{
		$model = phodevi::read_property('cpu', 'model');

		// Append the processor frequency to string
		if(($freq = phodevi::read_property('cpu', 'default-frequency')) > 0)
		{
			$model = str_replace($freq . 'GHz', '', $model); // we'll replace it if it's already in the string
			$model .= ' @ ' . $freq . 'GHz';
		}

		$core_count = phodevi::read_property('cpu', 'physical-core-count');
		$thread_count = phodevi::read_property('cpu', 'thread-count');
		if($core_count > 0 && $thread_count > $core_count)
		{
			$count_msg = pts_strings::plural_handler($core_count, 'Core') . ' / ' . $thread_count . ' Threads';
		}
		else
		{
			$count_msg = pts_strings::plural_handler($core_count, 'Core');
		}

		return $model . ' (' . $count_msg . ')';
	}
	public static function cpu_model_and_speed()
	{
		$model = phodevi::read_property('cpu', 'model');

		// Append the processor frequency to string
		if(($freq = phodevi::read_property('cpu', 'default-frequency')) > 0)
		{
			$model = str_replace($freq . 'GHz', '', $model); // we'll replace it if it's already in the string
			$model .= ' @ ' . $freq . 'GHz';
		}

		return $model;
	}
	public static function cpu_core_count()
	{
		$info = null;

		if(($n = getenv('NUM_CPU_CORES')) && is_numeric($n) && $n > 0)
		{
			// NUM_CPU_CORES can be used for overriding the number of exposed cores/threads to tests, matches the name of the env var set by PTS to test scripts
			$info = $n;
		}
		else if(($n = getenv('PTS_NPROC')) && is_numeric($n) && $n > 0)
		{
			// PTS_NPROC can be used for overriding the number of exposed cores/threads to tests
			$info = $n;
		}
		else if(($n = getenv('NUMBER_OF_PROCESSORS')) && is_numeric($n) && $n > 0)
		{
			// Should be used by Windows they have NUMBER_OF_PROCESSORS set and use this as an easy way to override CPUs exposed
			$info = $n;
		}
		else if(phodevi::is_linux())
		{
			$sl = phodevi::read_property('system', 'system-layer');
			if(is_file('/sys/devices/system/cpu/online') && ($sl == null || stripos($sl, 'lxc') === false))
			{
				$present = pts_file_io::file_get_contents('/sys/devices/system/cpu/online');

				if(isset($present[2]) && substr($present, 0, 2) == '0-')
				{
					$present = substr($present, 2);

					if(is_numeric($present))
					{
						$info = $present + 1;
					}
				}
			}
		}
		else if(phodevi::is_solaris())
		{
			$info = count(explode(PHP_EOL, trim(shell_exec('psrinfo'))));
		}
		else if(phodevi::is_bsd())
		{
			$info = intval(phodevi_bsd_parser::read_sysctl(array('hw.ncpufound', 'hw.ncpu')));
		}
		else if(phodevi::is_macos())
		{
			$info = intval(phodevi_bsd_parser::read_sysctl(array('hw.ncpu')));

			if(empty($info))
			{
				$info = phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'TotalNumberOfCores');
			}
		}

		if(phodevi::is_windows())
		{
			// Should be hit by the first NUMBER_OF_PROCESSORS env check...
			$logical_cores = array_sum(phodevi_windows_parser::get_wmi_object('Win32_Processor', 'NumberOfLogicalProcessors', true));
			if($logical_cores > $info || !is_numeric($info))
			{
				$info = $logical_cores;
			}
		}

		if($info == null && isset(phodevi::$vfs->cpuinfo))
		{
			$info = self::cpuinfo_thread_count();
		}

		return (is_numeric($info) && $info > 0 ? $info : 1);
	}
	public static function cpu_physical_core_count()
	{
		$physical_cores = null;

		if(phodevi::is_linux())
		{
			$physical_cores = phodevi_cpu::cpuinfo_core_count();

			if(empty($physical_cores) || $physical_cores == phodevi::read_property('cpu', 'thread-count'))
			{
				// Needed for POWER9 at least
				if(isset(phodevi::$vfs->lscpu) && ($t = strpos(phodevi::$vfs->lscpu, 'Core(s) per socket:')))
				{
					$lscpu = substr(phodevi::$vfs->lscpu, $t + strlen('Core(s) per socket:') + 1);
					$lscpu = substr($lscpu, 0, strpos($lscpu, PHP_EOL));
					$cores_per_socket = trim($lscpu);

					if($cores_per_socket > 1 && ($t = strpos(phodevi::$vfs->lscpu, 'Socket(s):')))
					{
						$lscpu = substr(phodevi::$vfs->lscpu, $t + strlen('Socket(s):') + 1);
						$lscpu = substr($lscpu, 0, strpos($lscpu, PHP_EOL));
						$sockets = trim($lscpu);
						if(is_numeric($sockets) && $sockets >= 1)
						{
							$physical_cores = $cores_per_socket * $sockets;
						}
					}
				}

			}
		}
		else if(phodevi::is_bsd())
		{
			// hw.cpu_topology_core_ids works at least on DragonFly BSD
			$ht_ids = intval(phodevi_bsd_parser::read_sysctl(array('hw.cpu_topology_ht_ids')));
			if($ht_ids == 2)
			{
				$info = intval(phodevi_bsd_parser::read_sysctl(array('hw.ncpu')));

				if($info > 1)
				{
					$physical_cores = $info / 2;
				}
			}
			else
			{
				$phys_ids = intval(phodevi_bsd_parser::read_sysctl(array('hw.cpu_topology_phys_ids')));
				$physical_cores = intval(phodevi_bsd_parser::read_sysctl(array('hw.cpu_topology_core_ids')));
				if($phys_ids > 0 && ($phys_ids * $physical_cores) <= phodevi::read_property('cpu', 'thread-count') && $physical_cores % 2 == 0)
				{
					$physical_cores = $phys_ids * $physical_cores;
				}
			}
		}
		else if(phodevi::is_macos())
		{
			$physical_cores = intval(phodevi_bsd_parser::read_sysctl(array('hw.physicalcpu')));
		}
		else if(phodevi::is_windows())
		{
			$physical_cores = array_sum(phodevi_windows_parser::get_wmi_object('Win32_Processor', 'NumberOfCores', true));
		}

		if(empty($physical_cores) || !is_numeric($physical_cores))
		{
			$physical_cores = phodevi::read_property('cpu', 'core-count');
		}

		return $physical_cores;
	}
	public static function cpu_thread_count()
	{
		$threads = null;
		if(phodevi::is_linux())
		{
			$threads = phodevi_cpu::cpuinfo_thread_count();
		}
		else
		{
			$threads = phodevi::read_property('cpu', 'core-count');
		}

		return $threads;
	}
	public static function cpu_node_count()
	{
		$node_count = 1;

		if(isset(phodevi::$vfs->lscpu) && ($t = strpos(phodevi::$vfs->lscpu, 'NUMA node(s):')))
		{
			$lscpu = substr(phodevi::$vfs->lscpu, $t + strlen('NUMA node(s):') + 1);
			$lscpu = substr($lscpu, 0, strpos($lscpu, PHP_EOL));
			$node_count = trim($lscpu);
		}

		return (is_numeric($node_count) && $node_count > 0 ? $node_count : 1);
	}
	public static function cpu_cache_size()
	{
		$cache_size = 0; // in KB

		if(phodevi::is_linux())
		{
			if(isset(phodevi::$vfs->lscpu) && ($t = strpos(phodevi::$vfs->lscpu, 'L3 cache:')))
			{
					$lscpu = substr(phodevi::$vfs->lscpu, $t + strlen('L3 cache:') + 1);
					$lscpu = substr($lscpu, 0, strpos($lscpu, PHP_EOL));
					if(!empty($lscpu) && ($x = stripos($lscpu, ' (')) !== false)
					{
						$lscpu = substr($lscpu, 0, $x);
					}
					$lscpu = trim($lscpu);
					$cache_size = pts_math::number_with_unit_to_mb($lscpu);
			}
			if(empty($cache_size) || !is_numeric($cache_size))
			{
				$cache_size = pts_math::unit_to_mb(self::cpuinfo_cache_size(), 'K');
			}
		}
		else if(phodevi::is_macos())
		{
			$cache_size = pts_math::number_with_unit_to_mb(phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'L3Cache'));
		}
		else if(phodevi::is_windows())
		{
			$cache_size = phodevi_windows_parser::get_wmi_object('Win32_Processor', 'L2CacheSize');
		}

		return $cache_size;
	}
	public static function cpu_default_frequency_mhz()
	{
		return self::cpu_default_frequency() * 1000;
	}
	public static function cpu_scaling_governor()
	{
		$scaling_governor = false;

		if(is_file('/sys/devices/system/cpu/cpu0/cpufreq/scaling_driver'))
		{
			$scaling_governor = pts_file_io::file_get_contents('/sys/devices/system/cpu/cpu0/cpufreq/scaling_driver') . ' ';
		}
		if(is_file('/sys/devices/system/cpu/cpu0/cpufreq/scaling_governor'))
		{
			$scaling_governor .= pts_file_io::file_get_contents('/sys/devices/system/cpu/cpu0/cpufreq/scaling_governor');
		}

		if(!empty($scaling_governor))
		{
			$append_notes = array();
			if(is_file('/sys/devices/system/cpu/cpufreq/boost'))
			{
				$boost = pts_file_io::file_get_contents('/sys/devices/system/cpu/cpufreq/boost');
				$boosted = null;

				if($boost === '0')
				{
					$boosted = 'Disabled';
				}
				else if($boost === '1')
				{
					$boosted = 'Enabled';
				}
				if($boosted != null)
				{
					$append_notes[] = 'Boost: ' . $boosted;
				}
			}
			if(is_file('/sys/devices/system/cpu/cpu0/cpufreq/energy_performance_preference'))
			{
				$epp = pts_file_io::file_get_contents('/sys/devices/system/cpu/cpu0/cpufreq/energy_performance_preference');

				if($epp != null)
				{
					$append_notes[] = 'EPP: ' . $epp;
				}
			}
			if(!empty($append_notes))
			{
				$scaling_governor = trim($scaling_governor) . ' (' . implode(', ', $append_notes) . ')';
			}
		}

		return trim($scaling_governor);
	}
	public static function cpu_power_management()
	{
		$pm = array();

		if(is_file('/sys/firmware/acpi/platform_profile'))
		{
			$platform_profile = pts_file_io::file_get_contents('/sys/firmware/acpi/platform_profile');
			if(!empty($platform_profile))
			{
				$pm[] = 'ACPI Platform Profile: ' . $platform_profile;
			}
		}

		if(is_file('/sys/bus/pci/devices/0000:00:04.0/workload_request/workload_type'))
		{
			// Intel INT340x Workload Type
			$workload_type = pts_file_io::file_get_contents('/sys/bus/pci/devices/0000:00:04.0/workload_request/workload_type');
			if(!empty($workload_type) && $workload_type != 'none')
			{
				$pm[] = 'INT340x Workload Type: ' . $workload_type;
			}
		}

		return implode(' - ', $pm);
	}
	public static function cpu_smt()
	{
		$smt = false;

		if(pts_client::executable_in_path('ppc64_cpu')) {
			$ppc64 = trim(shell_exec('ppc64_cpu --smt -n | grep SMT= | cut -d= -f2'));
			if(is_numeric($ppc64) && $ppc64 >= 1)
				$smt = $ppc64;
		}

		return trim($smt);
	}
	public static function is_genuine($cpu)
	{
		/*
			Real/Genuine CPUs should have:
			1. Contain more than one word in string
			2. Check vendor (to avoid QEMU, Virtual CPU, etc): Intel, VIA, AMD, ARM, SPARC
		*/

		return strpos($cpu, ' ') !== false && strpos($cpu, ' ') != strrpos($cpu, ' ') && pts_strings::has_in_istring($cpu, array('Intel', 'VIA', 'AMD', 'ARM', 'SPARC', 'Transmeta')) && stripos($cpu, 'unknown') === false;
	}
	public static function cpu_microcode_version()
	{
		$ucode_version = null;

		if(is_readable('/sys/devices/system/cpu/cpu0/microcode/version'))
		{
			$ucode_version = pts_file_io::file_get_contents('/sys/devices/system/cpu/cpu0/microcode/version');
		}
		if(empty($ucode_version) && isset(phodevi::$vfs->cpuinfo))
		{
			$ucode_version = self::read_cpuinfo_line('microcode');
		}
		else if(phodevi::is_windows())
		{
			$reg = shell_exec('reg query HKLM\HARDWARE\DESCRIPTION\System\CentralProcessor\0');
			if(($x = strpos($reg, 'Update Revision')) !== false)
			{
				$reg = substr($reg, $x);
				$reg = substr($reg, 0, strpos($reg, "\n"));
				$ucode = substr($reg, strrpos($reg, ' '));
				if(is_numeric($ucode))
				{
					$ucode_version = $ucode;
				}
			}
		}

		if(empty($ucode_version) && phodevi::is_macos())
		{
			$ucode_version = phodevi_bsd_parser::read_sysctl(array('machdep.cpu.microcode_version'));
		}

		return $ucode_version;
	}
	public static function cpu_default_frequency($cpu_core = 0)
	{
		// Find out the processor frequency
		$info = null;
		// First, the ideal way, with modern CPUs using CnQ or EIST and cpuinfo reporting the current
		if(phodevi::is_linux())
		{
			if(is_file('/sys/devices/system/cpu/cpu' . $cpu_core . '/cpufreq/scaling_max_freq'))
			{
				$info = pts_file_io::file_get_contents('/sys/devices/system/cpu/cpu' . $cpu_core . '/cpufreq/scaling_max_freq');
				$info = intval($info) / 1000000;

				if($info > 9)
				{
					// For some reason on Linux 3.10 the scaling_max_freq is reported as 25GHz...
					$info = null;
				}
			}
			if($info == null && isset(phodevi::$vfs->cpuinfo) && phodevi::read_property('system', 'kernel-architecture') != 'x86_64') // fall back for those without cpufreq
			{
				// Don't use this code path for x86_64 since for those systems the /sys reporting should work
				// and when that isn't the case, CPUFreq not loaded and thus reported here is usually dynamic frequency
				$cpu_mhz = self::read_cpuinfo_line('cpu MHz');
				$info = $cpu_mhz / 1000;

				if(empty($info))
				{
					$cpu_mhz = str_replace('MHz', '', self::read_cpuinfo_line('clock'));
					if(is_numeric($cpu_mhz))
					{
						$info = $cpu_mhz / 1000;
					}
				}
			}
		}
		else if($info == null && phodevi::is_bsd())
		{
			$info = phodevi_bsd_parser::read_sysctl(array('dev.cpu.0.freq_levels'));

			if($info != null)
			{
				// Popping the top speed off of dev.cpu.0.freq_levels should be the default/highest supported frequency
				$info = pts_arrays::first_element(explode(' ', str_replace('/', ' ', $info)));

				if(!is_numeric($info))
				{
					$info = null;
				}
			}

			if($info == null)
			{
				$info = phodevi_bsd_parser::read_sysctl(array('hw.acpi.cpu.px_global', 'machdep.est.frequency.target', 'hw.cpuspeed'));
			}

			if($info == null)
			{
				// dev.cpu.0.freq seems to be the real/current frequency, affected by power management, etc so only use as last fallback
				$info = phodevi_bsd_parser::read_sysctl(array('dev.cpu.0.freq'));
			}

			if(is_numeric($info))
			{
				$info = $info / 1000;
			}
			else
			{
				$info = null;
			}
		}
		else if($info == null && phodevi::is_windows())
		{
			$info = phodevi_windows_parser::get_wmi_object('win32_processor', 'MaxClockSpeed');
			if($info != null && is_numeric($info))
			{
				$info = $info / 1000;
			}
			else
			{
				$info = null;
			}
		}
		else if($info == null)
		{
			$freq_sensor = new cpu_freq(0, NULL);
			$info = phodevi::read_sensor($freq_sensor);
			unset($freq_sensor);

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

		if(isset(phodevi::$vfs->cpuinfo))
		{
			$physical_cpu_ids = phodevi_linux_parser::read_cpuinfo('physical id');
			$physical_cpu_count = count(array_unique($physical_cpu_ids));

			$cpu_strings = phodevi_linux_parser::read_cpuinfo(array('model name', 'Processor', 'cpu', 'cpu model', 'Model Name'));

			$cpu_strings_unique = array_unique($cpu_strings);

			if($physical_cpu_count == 1 || empty($physical_cpu_count))
			{
				// Just one processor
				if(isset($cpu_strings[0]) && !empty($cpu_strings[0]))
				{
					// This fixes some Intel CPUs only displaying "Intel" for model due to " (R) " in strings with below check
					$cpu_strings[0] = str_replace(' (R)', ' ', $cpu_strings[0]);
				}
				if(isset($cpu_strings[0]) && ($cut = strpos($cpu_strings[0], ' (')) !== false)
				{
					$cpu_strings[0] = substr($cpu_strings[0], 0, $cut);
				}

				$info = isset($cpu_strings[0]) ? $cpu_strings[0] : null;

				// Fallback CPU detection
				switch($info)
				{
					case 'AMD Eng Sample: 100-000000163_43/29_Y':
						if(count($physical_cpu_ids) == 128)
						{
							$info = 'AMD Ryzen Threadripper 3990X 64-Core';
						}
						break;
					default:
						if(!empty($info))
						{
							$info = str_replace(': ', ' ', $info);
						}
						break;
				}
				if(!empty($info) && strpos($info, 'ARM') !== false)
				{
					if(is_dir('/sys/devices/system/exynos-core/') && stripos($info, 'Exynos') === false)
					{
						$info = 'Exynos ' . $info;
					}
				}
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
		else if(phodevi::is_macos())
		{
			$info = phodevi_bsd_parser::read_sysctl('machdep.cpu.brand_string');

			if(empty($info) || strtolower($info) == 'apple processor')
			{
				$info = phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'Chip');
			}

			if(empty($info))
			{
				$info = phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'ProcessorName');
			}
		}
		else if(phodevi::is_windows())
		{
			$info = phodevi_windows_parser::get_wmi_object('win32_processor', 'Name');
			$cpu_count = count(phodevi_windows_parser::get_wmi_object('Win32_Processor', 'DeviceID', true));
			if($cpu_count > 1)
			{
				$info = $cpu_count . ' x ' . $info;
			}
			if(!$info)
			{
				$info = getenv('PROCESSOR_IDENTIFIER');
			}
		}

		if(empty($info) || strpos($info, 'rev ') !== false)
		{
			if(phodevi::is_linux())
			{
				$new_info = null;
				$implementer = phodevi_linux_parser::read_cpuinfo_single('CPU implementer');
				$part = phodevi_linux_parser::read_cpuinfo_single('CPU part');
				if($implementer == '0x41' || $implementer == '0x50')
				{
					$architecture = phodevi_linux_parser::read_cpuinfo_single('CPU architecture');
					switch($architecture)
					{
						case '7':
							$new_info = 'ARMv7';
							break;
						case '8':
						case 'AArch64':
							$new_info = 'ARMv8';
							break;
					}
					// parts listed @ https://gcc.gnu.org/git/?p=gcc.git;a=blob;f=gcc/config/arm/arm-cpus.in
					switch($part)
					{
						case '0xc07':
							$new_info .= ' Cortex-A7';
							break;
						case '0xc20':
							$new_info .= ' Cortex-M7';
							break;
						case '0xc09':
							$new_info .= ' Cortex-A9';
							break;
						case '0xc0f':
							$new_info .= ' Cortex-A15';
							break;
						case '0xc0e':
							$new_info .= ' Cortex-A12';
							break;
						case '0xd01':
							$new_info .= ' Cortex-A32';
							break;
						case '0xd03':
							$new_info .= ' Cortex-A53';
							break;
						case '0xd05':
							$new_info .= ' Cortex-A55';
							break;
						case '0xd07':
							$new_info .= ' Cortex-A57';
							break;
						case '0xd06':
							$new_info .= ' Cortex-A65';
							break;
						case '0xd23':
							$new_info .= ' Cortex-M85';
							break;
						case '0xd43':
							$new_info .= ' Cortex-A65AE';
							break;
						case '0xd08':
							$new_info .= ' Cortex-A72';
							break;
						case '0xd09':
							$new_info .= ' Cortex-A73';
							break;
						case '0xd0a':
							$new_info .= ' Cortex-A75';
							break;
						case '0xd0b':
							$new_info .= ' Cortex-A76';
							break;
						case '0xd0e':
							$new_info .= ' Cortex-A76AE';
							break;
						case '0xd0d':
							$new_info .= ' Cortex-A77';
							break;
						case '0xd0c':
							$new_info .= ' Neoverse-N1';
							break;
						case '0xd40':
							$new_info .= ' Neoverse-V1';
							break;
						case '0xd41':
							$new_info .= ' Cortex-A78';
							break;
						case '0xd4d':
							$new_info .= ' Cortex-A715';
							break;
						case '0xd42':
							$new_info .= ' Cortex-A78E';
							break;
						case '0xd44':
							$new_info .= ' Cortex-X1';
							break;
						case '0xd4b':
							$new_info .= ' Cortex-A78C';
							break;
						case '0xd4c':
							$new_info .= ' Cortex-X1C';
							break;
						case '0xd46':
							$new_info .= ' Cortex-A510';
							break;
						case '0xd47':
							$new_info .= ' Cortex-A710';
							break;
						case '0xd48':
							$new_info .= ' Cortex-X2';
							break;
						case '0xd49':
							$new_info .= ' Neoverse-N2';
							break;
						case '0xd4a':
							$new_info .= ' Neoverse-E1';
							break;
						case '0xd4e':
							$new_info .= ' Cortex-X3';
							break;
						case '0xd4f':
							$new_info .= ' Neoverse-V2';
							break;
						case '0xd13':
							$new_info .= ' Cortex-R52';
							break;
						case '0xd14':
							$new_info .= ' Cortex-R82AE';
							break;
						case '0xd15':
							$new_info .= ' Cortex-R82';
							break;
						case '0xd20':
							$new_info .= ' Cortex-M23';
							break;
						case '0xd21':
							$new_info .= ' Cortex-M33';
							break;
						case '0xd80':
							$new_info .= ' Cortex-A520';
							break;
						case '0xd81':
							$new_info .= ' Cortex-A720';
							break;
						case '0xd82':
							$new_info .= ' Cortex-X4';
							break;
						case '0xd83':
							$new_info .= ' Neoverse-V3AE';
							break;
						case '0xd84':
							$new_info .= ' Neoverse-V3';
							break;
						case '0xd85':
							$new_info .= ' Cortex-X925';
							break;
						case '0xd88':
							$new_info .= ' Cortex-A520E';
							break;
						case '0xd89':
							$new_info .= ' Cortex-A720E';
							break;
						case '0xd8e':
							$new_info .= ' Neoverse-N3';
							break;
					}
				}
				else if($implementer == '0x61')
				{
					$new_info = 'Apple';
					// https://github.com/AsahiLinux/linux/blob/asahi/arch/arm64/include/asm/cputype.h
					switch($part)
					{
						case '0x022':
						case '0x023':
							$new_info .= ' M1';
							break;
						case '0x024':
						case '0x025':
							$new_info .= ' M1 Pro';
							break;
						case '0x028':
						case '0x029':
							$new_info .= ' M1 Max';
							break;
						case '0x032':
						case '0x033':
							$new_info .= ' M2';
							break;
						case '0x034':
						case '0x035':
							$new_info .= ' M2 Pro';
							break;
						case '0x038':
						case '0x039':
							$new_info .= ' M2 Max';
							break;
					}
				}
				else if($implementer == '0x46')
				{
					$new_info = 'Fujitsu';
					switch($part)
					{
						case '0x001':
							$new_info .= ' A64FX';
							break;
						case '0x003':
							$new_info .= ' MONAKA';
							break;
					}
				}
				else if($implementer == '0x48')
				{
					$new_info = 'HiSilicon';
					switch($part)
					{
						case '0xd01':
							$new_info .= ' TSV110';
							break;
					}
				}
				else if($implementer == '0x51')
				{
					$new_info = 'Qualcomm';
					switch($part)
					{
						case '0x804':
						case '0x805':
							$new_info .= ' Cortex-A76';
							break;
					}
				}
				else if($implementer == '0x53')
				{
					$new_info = 'Samsung';
				}
				else if($implementer == '0xc0')
				{
					$new_info = 'Ampere';
					switch($part)
					{
						case '0xac3':
						case '0xac4':
						case '0xac5':
							$new_info .= 'One';
							break;
					}
				}
				else if($implementer == '0x70')
				{
					$new_info = 'Phytium';
					switch($part)
					{
						case '0x303':
							$new_info .= ' E2000';
							break;
						case '0x663':
							$new_info .= ' FT2000A';
							break;
					}
				}
				else if($implementer == '0x6d')
				{
					$new_info = 'Microsoft';
					switch($part)
					{
						case '0xd49':
							$new_info .= ' Azure Cobalt 100';
							break;
					}
				}

				if(strpos(phodevi::$vfs->dmesg, 'Ampere eMAG') !== false || stripos(pts_file_io::file_get_contents_if_exists('/sys/devices/virtual/dmi/id/sys_vendor'), 'Ampere') !== false || stripos(pts_file_io::file_get_contents_if_exists('/sys/devices/virtual/dmi/id/bios_vendor'), 'Ampere') !== false)
				{
					$product_family =  pts_file_io::file_get_contents_if_exists('/sys/devices/virtual/dmi/id/product_family');
					$sys_vendor =  pts_file_io::file_get_contents_if_exists('/sys/devices/virtual/dmi/id/sys_vendor');
					if(stripos($product_family, 'Quicksilver') !== false)
					{
						$new_info = 'Ampere Altra ' . $new_info;
					}
					else if(stripos($sys_vendor, 'Lenovo') !== false || stripos($product_family, 'eMAG') !== false)
					{
						$new_info = 'Ampere eMAG ' . $new_info;
					}
					else
					{
						$new_info = 'Ampere ' . $new_info;
					}
				}
				else if(strpos(phodevi::$vfs->dmesg, 'thunderx') !== false || strpos(phodevi::$vfs->dmesg, 'Cavium erratum') !== false)
				{
					// Haven't found a better way to detect ThunderX as not exposed via cpuinfo, etc
					$new_info = 'Cavium ThunderX ' . $new_info;
				}
				else if(strpos(phodevi::$vfs->dmesg, 'rockchip-cpuinfo') !== false)
				{
					// Haven't found a better way to detect Rockchip as not exposed via cpuinfo, etc
					$new_info = 'Rockchip ' . $new_info;
				}
				else if(is_file('/sys/devices/system/cpu/cpu0/cpufreq/scaling_driver'))
				{
					$scaling_driver = file_get_contents('/sys/devices/system/cpu/cpu0/cpufreq/scaling_driver');
					if(strpos($scaling_driver, 'meson_') !== false)
					{
						$new_info = 'Amlogic ' . $new_info;
					}
				}

				if(!empty($new_info))
				{
					$info = trim($new_info);
				}

				if(empty($info))
				{
					$isa = phodevi_linux_parser::read_cpuinfo_single('isa');
					$uarch = phodevi_linux_parser::read_cpuinfo_single('uarch');

					if(!empty($uarch) && stripos($isa, 'rv') !== false && strpos($uarch, 'sifive') !== false)
					{
						$info = 'SiFive RISC-V';
					}
					else if(!empty($isa))
					{
						$info = $isa;
					}
				}
			}


			if(empty($info))
			{
				$info = 'Unknown';
			}
		}
		else
		{
			if(($strip_point = strpos($info, '@')) > 0)
			{
				$info = trim(substr($info, 0, $strip_point)); // stripping out the reported freq, since the CPU could be overclocked, etc
			}

			$info = pts_strings::strip_string($info);

			// It seems Intel doesn't report its name when reporting Pentium hardware
			if(strpos($info, 'Pentium') !== false && stripos($info, 'Intel') === false)
			{
				$info = 'Intel ' . $info;
			}

			if(substr($info, 0, 5) == 'Intel')
			{
				$cpu_words = explode(' ', $info);
				$cpu_words_count = count($cpu_words);

				// Convert strings like 'Intel Core i7 M 620' -> 'Intel Core i7 620M' and 'Intel Core i7 X 990' -> 'Intel Core i7 990X' to better reflect Intel product marketing names
				if($cpu_words_count > 4 && is_numeric($cpu_words[($cpu_words_count - 1)]) && strlen($cpu_words[($cpu_words_count - 2)]) == 1 && strlen($cpu_words[($cpu_words_count - 3)]) == 2)
				{
					$cpu_words[($cpu_words_count - 1)] .= $cpu_words[($cpu_words_count - 2)];
					unset($cpu_words[($cpu_words_count - 2)]);
					$info = implode(' ', $cpu_words);
				}
			}
			else if(($gen = strpos($info, ' Gen')) !== false && ($intel = stripos($info, 'Intel ')) !== false && $gen < $intel)
			{
				// Tiger Lake reports "11th Gen Intel" as CPU string
				$info = substr($info, $intel);
			}
		}

		if(($c = strpos($info, '-Core ')) !== false && $c < strpos($info, ' '))
		{
			// At least on newer macOS, they append strings like 6-Core prior to Intel
			$info = substr($info, ($c + 6));
		}

		return $info;
	}
	public static function get_cpu_feature_constants()
	{
		return array(
			'sse2' => (1 << 1), // SSE 2
			'sse3' => (1 << 2), // SSE 3
			'sse4a' => (1 << 3), // SSE 4a
			'sse4_1' => (1 << 4), // SSE 4.1
			'sse4_2' => (1 << 5), // SSE 4.2
			//'sse5' => (1 << 6), // SSE 5
			'avx' => (1 << 7), // AVX
			'avx2' => (1 << 8), // AVX2
			'aes' => (1 << 9), // AES
			'epb' => (1 << 10), // EPB
			'svm' => (1 << 11), // AMD SVM (Virtualization)
			'vmx' => (1 << 12), // Intel Virtualization
			'xop' => (1 << 13), // AMD XOP Instruction Set
			'fma3' => (1 << 14), // FMA3 Instruction Set
			'fma4' => (1 << 15), // FMA4 Instruction Set
			'fma' => (1 << 16), // FMA4 Instruction Set
			'rdrand' => (1 << 17), // Intel Bull Mountain RDRAND - Ivy Bridge
			'fsgsbase' => (1 << 18), // FSGSBASE - Ivy Bridge AVX
			'bmi2' => (1 << 19), // Intel Haswell has BMI2
			'avx512cd' => (1 << 20), // AVX-512
			'avx512_vnni' => (1 << 21), // AVX-512 VNNI (DL BOOST)
			'avx512_bf16' => (1 << 22), // AVX-512 BFloat16
			'amx_tile' => (1 << 23), // AMX
			'vaes' => (1 << 24),
			);
	}
	public static function prominent_cpu_features()
	{
		return array(
			'sse4_2' => 'SSE 4.2', // SSE 4.2
			'avx' => 'AVX', // AVX
			'avx2' => 'AVX2', // AVX2
			'aes' => 'AES', // AES
			'vaes' => 'VAES', // AES
			'svm' => 'AMD SVM', // AMD SVM (Virtualization)
			'vmx' => 'Intel VT-x', // Intel Virtualization
			'fma' => 'FMA', // FMA Instruction Set
			'fma3' => 'FMA3', // FMA3 Instruction Set
			'fma4' => 'FMA4', // FMA4 Instruction Set
			'rdrand' => 'RdRand', // Intel Bull Mountain RDRAND - Ivy Bridge
			'fsgsbase' => 'FSGSBASE', // FSGSBASE - Ivy Bridge AVX
			'bmi2' => 'BMI2', // Intel Haswell has BMI2
			'avx512cd' => 'AVX-512', // AVX-512
			'avx512_vnni' => 'AVX-512 VNNI / DL-BOOST', // AVX-512 VNNI (DL BOOST)
			'avx512_bf16' => 'AVX-512 BFloat16', // AVX-512 BFloat16
			'amx_tile' => 'AMX', // Advanced Matrix Extensions
			);
	}
	public static function interesting_instructions()
	{
		return array(
		'MMX' => array('emms', 'maskmovq', 'movq', 'movntq', 'packssdw', 'packsswb', 'packuswb', 'paddb', 'paddd', 'paddsb', 'paddsw', 'paddusb', 'paddusw', 'paddw', 'pand', 'pandn', 'pavgusb', 'pavgb', 'pavgw', 'pcmpeqb', 'pcmpeqd', 'pcmpeqw', 'pcmpgtb', 'pcmpgtd', 'pcmpgtw', 'pextrw', 'pinsrw', 'pmaddwd', 'pmaxsw', 'pmaxub', 'pminsw', 'pminub', 'pmovmskb', 'pmulhw', 'pmullw', 'pmulhuw', 'por', 'psadbw', 'pshufw', 'pslld', 'psllq', 'psllw', 'psrad', 'psraw', 'psrld', 'psrlq', 'psrlw', 'psubb', 'psubd', 'psubsb', 'psubsw', 'psubusb', 'psubusw', 'psubw', 'punpckhbw', 'punpckhdq', 'punpckhwd', 'punpcklbw', 'punpckldq', 'punpcklwd', 'pxor'),
		'SSE' => array('addps', 'addss', 'andnps', 'andps', 'cmpeqps', 'cmpeqss', 'cmpleps', 'cmpless', 'cmpltps', 'cmpltss', 'cmpneqps', 'cmpneqss', 'cmpnleps', 'cmpnless', 'cmpnltps', 'cmpnltss', 'cmpordps', 'cmpordss', 'cmpps', 'cmpss', 'cmpunordps', 'cmpunordss', 'comiss', 'cvtpi2ps', 'cvtps2pi', 'cvtsi2ss', 'cvtss2si', 'cvttps2pi', 'cvttss2si', 'divps', 'divss', 'ldmxcsr', 'maxps', 'maxss', 'minps', 'minss', 'movaps', 'movhlps', 'movhps', 'movlhps', 'movlps', 'movmskps', 'movntps', 'movss', 'movups', 'mulps', 'mulss', 'orps', 'rcpps', 'rcpss', 'rsqrtps', 'rsqrtss', 'shufps', 'sqrtps', 'sqrtss', 'stmxcsr', 'subps', 'subss', 'ucomiss', 'unpckhps', 'unpcklps', 'xorps'),
		'SSE2' => array('addpd', 'addsd', 'andnpd', 'andpd', 'clflush', 'cmpeqpd', 'cmpeqsd', 'cmplepd', 'cmplesd', 'cmpltpd', 'cmpltsd', 'cmpneqpd', 'cmpneqsd', 'cmpnlepd', 'cmpnlesd', 'cmpnltpd', 'cmpnltsd', 'cmpordpd', 'cmpordsd', 'cmppd', 'cmpunordpd', 'cmpunordsd', 'comisd', 'cvtdq2pd', 'cvtdq2ps', 'cvtpd2dq', 'cvtpd2pi', 'cvtpd2ps', 'cvtpi2pd', 'cvtps2dq', 'cvtps2pd', 'cvtsd2si', 'cvtsd2ss', 'cvtsi2sd', 'cvtss2sd', 'cvttpd2dq', 'cvttpd2pi', 'cvttps2dq', 'cvttsd2si', 'divpd', 'divsd', 'maskmovdqu', 'maxpd', 'maxsd', 'minpd', 'minsd', 'movapd', 'movdq2q', 'movdqa', 'movdqu', 'movhpd', 'movlpd', 'movmskpd', 'movntdq', 'movnti', 'movntpd', 'movq2dq', 'movupd', 'mulpd', 'mulsd', 'orpd', 'paddq', 'pmuludq', 'pshufd', 'pshufhw', 'pshuflw', 'pslldq', 'psrldq', 'psubq', 'punpckhqdq', 'punpcklqdq', 'shufpd', 'sqrtpd', 'sqrtsd', 'subpd', 'subsd', 'ucomisd', 'unpckhpd', 'unpcklpd', 'xorpd', 'movd'),
		'SSE3' => array('addsubpd', 'addsubps', 'fisttp', 'haddpd', 'haddps', 'hsubpd', 'hsubps', 'lddqu', 'monitor', 'movddup', 'movshdup', 'movsldup', 'mwait'),
		'SSSE3' => array('pabsb', 'pabsd', 'pabsw', 'palignr', 'phaddd', 'phaddsw', 'phaddw', 'phsubd', 'phsubsw', 'phsubw', 'pmaddubsw', 'pmulhrsw', 'pshufb', 'psignb', 'psignd', 'psignw'),
		'SSE4_1' => array('blendpd', 'blendps', 'blendvpd', 'blendvps', 'dppd', 'dpps', 'extractps', 'insertps', 'movntdqa', 'mpsadbw', 'packusdw', 'pblendvb', 'pblendw', 'pcmpeqq', 'pextrb', 'pextrd', 'pextrq', 'phminposuw', 'pinsrb', 'pinsrd', 'pinsrq', 'pmaxsb', 'pmaxsd', 'pmaxud', 'pmaxuw', 'pminsb', 'pminsd', 'pminud', 'pminuw', 'pmovsxbd', 'pmovsxbq', 'pmovsxbw', 'pmovsxdq', 'pmovsxwd', 'pmovsxwq', 'pmovzxbd', 'pmovzxbq', 'pmovzxbw', 'pmovzxdq', 'pmovzxwd', 'pmovzxwq', 'pmuldq', 'pmulld', 'ptest', 'roundpd', 'roundps', 'roundsd', 'roundss'),
		'SSE4_2' => array('crc32', 'pcmpestri', 'pcmpestrm', 'pcmpgtq', 'pcmpistri', 'pcmpistrm', 'popcnt'),
		'SSE4A' => array('extrq', 'insertq', 'movntsd', 'movntss'),
		'AVX' => 'VBROADCASTSS VBROADCASTSD VBROADCASTF128 VINSERTF128 VEXTRACTF128 VMASKMOVPS VPERMILPS VPERMILPD VPERM2F128 VZEROALL VZEROUPPER',
		'AVX2' => 'VPBROADCASTB VPBROADCASTW VPBROADCASTD VPBROADCASTQ VINSERTI128 VEXTRACTI128 VGATHERDPD VGATHERQPD VGATHERDPS VGATHERQPS VPGATHERDD VPGATHERDQ VPGATHERQD VPGATHERQQ VPMASKMOVD VPMASKMOVQ VPERMPS VPERMD VPERMPD VPERMQ VPERM2I128 VPBLENDD VPSLLVD VPSLLVQ  VPSRLVD VPSRLVQ  VPSRAVD',
		'AES' => 'AESENC AESENCLAST AESDEC AESDECLAST AESKEYGENASSIST AESIMC',
		'AVX512' => 'AVX512F AVX512CD AVX512DQ AVX512PF AVX512ER AVX512VL AVX512BW AVX512IFMA AVX512VBMI AVX512VBMI2 AVX512VAES AVX512BITALG AVX5124FMAPS AVX512VPCLMULQDQ AVX512GFNI AVX512_VNNI AVX5124VNNIW AVX512VPOPCNTDQ AVX512_BF16 avx512vp2intersect',
		'VAES' => 'VAESDEC VAESDECLAST VAESENC VAESENCLAST VPCLMULQDQ',
		'AVX-VNNI' => 'vpdpbusd vpdpwssd vpdpbusds vpdpwssds',
		'SERIALIZE' => 'serialize',
		'WAITPKG' => 'umwait tpause umonitor',
		'ENQCMD' => 'enqcmd enqcmds',
		'MOVDIRI' => 'movdiri movdir64b',
		'CLWB' => 'clwb',
		'RDPRU' => 'rdpru',
		'FSGSBASE' => 'RDFSBASE RDGSBASE WRFSBASE WRGSBASE',
		'AMX' => 'LDTILECFG STTILECFG TILELOADD TILELOADDT1 TILESTORED TILERELEASE TILEZERO TDPBF16PS',
		'FMA' => array('vfmadd123pd', 'vfmadd123ps', 'vfmadd123sd', 'vfmadd123ss', 'vfmadd132pd', 'vfmadd132ps', 'vfmadd132sd', 'vfmadd132ss', 'vfmadd213pd', 'vfmadd213ps', 'vfmadd213sd', 'vfmadd213ss', 'vfmadd231pd', 'vfmadd231ps', 'vfmadd231sd', 'vfmadd231ss', 'vfmadd312pd', 'vfmadd312ps', 'vfmadd312sd', 'vfmadd312ss', 'vfmadd321pd', 'vfmadd321ps', 'vfmadd321sd', 'vfmadd321ss', 'vfmaddsub123pd', 'vfmaddsub123ps', 'vfmaddsub132pd', 'vfmaddsub132ps', 'vfmaddsub213pd', 'vfmaddsub213ps', 'vfmaddsub231pd', 'vfmaddsub231ps', 'vfmaddsub312pd', 'vfmaddsub312ps', 'vfmaddsub321pd', 'vfmaddsub321ps', 'vfmsub123pd', 'vfmsub123ps', 'vfmsub123sd', 'vfmsub123ss', 'vfmsub132pd', 'vfmsub132ps', 'vfmsub132sd', 'vfmsub132ss', 'vfmsub213pd', 'vfmsub213ps', 'vfmsub213sd', 'vfmsub213ss', 'vfmsub231pd', 'vfmsub231ps', 'vfmsub231sd', 'vfmsub231ss', 'vfmsub312pd', 'vfmsub312ps', 'vfmsub312sd', 'vfmsub312ss', 'vfmsub321pd', 'vfmsub321ps', 'vfmsub321sd', 'vfmsub321ss', 'vfmsubadd123pd', 'vfmsubadd123ps', 'vfmsubadd132pd', 'vfmsubadd132ps', 'vfmsubadd213pd', 'vfmsubadd213ps', 'vfmsubadd231pd', 'vfmsubadd231ps', 'vfmsubadd312pd', 'vfmsubadd312ps', 'vfmsubadd321pd', 'vfmsubadd321ps', 'vfnmadd123pd', 'vfnmadd123ps', 'vfnmadd123sd', 'vfnmadd123ss', 'vfnmadd132pd', 'vfnmadd132ps', 'vfnmadd132sd', 'vfnmadd132ss', 'vfnmadd213pd', 'vfnmadd213ps', 'vfnmadd213sd', 'vfnmadd213ss', 'vfnmadd231pd', 'vfnmadd231ps', 'vfnmadd231sd', 'vfnmadd231ss', 'vfnmadd312pd', 'vfnmadd312ps', 'vfnmadd312sd', 'vfnmadd312ss', 'vfnmadd321pd', 'vfnmadd321ps', 'vfnmadd321sd', 'vfnmadd321ss', 'vfnmsub123pd', 'vfnmsub123ps', 'vfnmsub123sd', 'vfnmsub123ss', 'vfnmsub132pd', 'vfnmsub132ps', 'vfnmsub132sd', 'vfnmsub132ss', 'vfnmsub213pd', 'vfnmsub213ps', 'vfnmsub213sd', 'vfnmsub213ss', 'vfnmsub231pd', 'vfnmsub231ps', 'vfnmsub231sd', 'vfnmsub231ss', 'vfnmsub312pd', 'vfnmsub312ps', 'vfnmsub312sd', 'vfnmsub312ss', 'vfnmsub321pd', 'vfnmsub321ps', 'vfnmsub321sd', 'vfnmsub321ss'),
		'BMI2' => 'BZHI MULX PDEP PEXT RORX SARX SHRX SHLX',
		);
	}
	public static function interesting_instructions_names()
	{
		return array(
			//'MMX' => 'MMX',
			'SSE2' => 'SSE2',
			'SSE3' => 'SSE3',
			'SSSE3' => 'SSSE3',
			'SSE4_2' => 'SSE 4.2',
			'SSE4A' => 'SSE4A',
			'AVX' => 'Advanced Vector Extensions',
			'AVX2' => 'Advanced Vector Extensions 2',
			'AVX512' => 'Advanced Vector Extensions 512',
			'AMX' => 'Advanced Matrix Extensions',
			'AES' => 'Advanced Encryption Standard',
			'VAES' => 'Vector AES',
			'AVX-VNNI' => 'AVX Vector Neural Network Instructions',
			'SERIALIZE' => 'SERIALIZE',
			'WAITPKG' => 'WAITPKG / UMWAIT / TPAUSE',
			'ENQCMD' => 'Data Streaming Accelerator',
			'FSGSBASE' => 'FSGSBASE',
			'MOVDIRI' => 'MOVDIRx',
			'CLWB' => 'Cache Line Write Back',
			'RDPRU' => 'Read Processor Register',
			'FMA' => 'FMA',
			'BMI2' => 'Bit Manipulation Instruction Set 2',
			);
	}
	public static function prominent_cpu_bugs()
	{
		return array(
			'cpu_meltdown' => 'Meltdown',
			'spectre_v1' => 'Spectre V1',
			'spectre_v2' => 'Spectre V2',
			'spec_store_bypass' => 'Spectre V4 / SSBD',
			'l1tf' => 'L1 Terminal Fault / Foreshadow',
			'mds' => 'Microarchitectural Data Sampling',
			'swapgs' => 'SWAPGS',
			'itlb_multihit' => 'iTLB Multihit',
			'taa' => 'TSX Asynchronous Abort',
			);
	}
	public static function get_cpu_family()
	{
		$family = null;
		if(phodevi::is_linux())
		{
			$cpuinfo = phodevi_linux_parser::cpuinfo_to_array();
			$family = isset($cpuinfo['cpu family']) ? $cpuinfo['cpu family'] : $family;
		}
		else if(phodevi::is_windows())
		{
			$processor_identifier = explode(' ', getenv('PROCESSOR_IDENTIFIER'));
			if(($x = array_search('Family', $processor_identifier)) !== false)
			{
				$family = $processor_identifier[($x + 1)];
			}
		}
		else if(phodevi::is_macos())
		{
			$family = phodevi_bsd_parser::read_sysctl(array('machdep.cpu.family'));
		}

		return $family;
	}
	public static function get_cpu_model()
	{
		$model = null;
		if(phodevi::is_linux())
		{
			$cpuinfo = phodevi_linux_parser::cpuinfo_to_array();
			$model = isset($cpuinfo['model']) ? $cpuinfo['model'] : $model;
		}
		else if(phodevi::is_windows())
		{
			$processor_identifier = explode(' ', getenv('PROCESSOR_IDENTIFIER'));
			if(($x = array_search('Model', $processor_identifier)) !== false)
			{
				$model = $processor_identifier[($x + 1)];
			}
		}
		else if(phodevi::is_macos())
		{
			$model = phodevi_bsd_parser::read_sysctl(array('machdep.cpu.model'));
		}

		return $model;
	}
	public static function get_core_name($family = false, $model = false, $cpu_string = null)
	{
		if($family === false && $model === false && PTS_IS_CLIENT)
		{
			$family = phodevi::read_property('cpu', 'cpu-family');
			$model = phodevi::read_property('cpu', 'cpu-model');
			$cpu_string = phodevi::read_property('cpu', 'model');
		}

		// Useful: https://en.wikichip.org/wiki/amd/cpuid / https://en.wikichip.org/wiki/intel/cpuid
		// https://github.com/torvalds/linux/blob/master/arch/x86/include/asm/intel-family.h
		$amd_map = array(
			14 => array(
				1 => 'Bobcat',
				2 => 'Bobcat',
				),
			15 => array(
				1 => 'Bulldozer',
				2 => 'Piledriver',
				5 => 'Sledgehammer',
				6 => 'Barcelona',
				10 => 'Piledriver',
				13 => 'Piledriver',
				30 => 'Steamroller',
				33 => 'Italy',
				35 => 'Denmark',
				37 => 'Troy',
				65 => 'Santa Rosa',
				67 => 'Santa Ana',
				72 => 'Taylor',
				75 => 'Windsor',
				104 => 'K8',
				107 => 'K8',
				),
			16 => array(
				0 => 'Jaguar',
				2 => 'K10',
				4 => 'Shanghai',
				5 => 'Rana',
				6 => 'Regor',
				8 => 'Istanbul',
				9 => 'Maranello',
				10 => 'K10',
				30 => 'Jaguar',
				),
			17 => array(
				3 => 'K8',
				),
			18 => array(
				1 => 'Llano',
				),
			20 => array(
				1 => 'Bobcat',
				2 => 'Ontaro',
				),
			21 => array(
				1 => 'Bulldozer',
				2 => 'Bulldozer',
				16 => 'Piledriver',
				19 => 'Piledriver',
				48 => 'Steamroller',
				56 => 'Steamroller',
				96 => 'Excavator',
				101 => 'Excavator',
				112 => 'Excavator',
				),
			22 => array(
				0 => 'Jaguar',
				48 => 'Carrizo',
				),
			23 => array(
				1 => 'Zen',
				17 => 'Zen',
				24 => 'Zen',
				8 => 'Zen+',
				49 => 'Zen 2',
				96 => 'Zen 2',
				104 => 'Zen 2',
				113 => 'Zen 2',
				144 => 'Zen 2',
				160 => 'Zen 2',
				),
			25 => array(
				0 => 'Zen 3',
				1 => 'Zen 3',
				8 => 'Zen 3',
				16 => 'Zen 4',
				17 => 'Zen 4',
				18 => 'Zen 4',
				19 => 'Zen 4',
				20 => 'Zen 4',
				21 => 'Zen 4',
				22 => 'Zen 4',
				23 => 'Zen 4',
				24 => 'Zen 4',
				25 => 'Zen 4',
				26 => 'Zen 4',
				27 => 'Zen 4',
				28 => 'Zen 4',
				29 => 'Zen 4',
				30 => 'Zen 4',
				31 => 'Zen 4',
				32 => 'Zen 3',
				33 => 'Zen 3',
				47 => 'Zen 3',
				48 => 'Zen 3',
				50 => 'Zen 3',
				64 => 'Zen 3', // Per Linux patches, Yellow Carp is 0x40 to 0x4f reserved
				65 => 'Zen 3',
				66 => 'Zen 3',
				67 => 'Zen 3',
				68 => 'Zen 3',
				69 => 'Zen 3',
				70 => 'Zen 3',
				71 => 'Zen 3',
				72 => 'Zen 3',
				73 => 'Zen 3',
				74 => 'Zen 3',
				75 => 'Zen 3',
				76 => 'Zen 3',
				77 => 'Zen 3',
				78 => 'Zen 3',
				79 => 'Zen 3', // end of Yellow Carp
				80 => 'Zen 3',
				96 => 'Zen 4',
				97 => 'Zen 4',
				112 => 'Zen 4',
				116 => 'Zen 4', // Ryzen Z1 Extreme
				117 => 'Zen 4',
				120 => 'Zen 4',
				160 => 'Zen 4',
				161 => 'Zen 4',
				162 => 'Zen 4',
				163 => 'Zen 4',
				164 => 'Zen 4',
				165 => 'Zen 4',
				166 => 'Zen 4',
				167 => 'Zen 4',
				168 => 'Zen 4',
				169 => 'Zen 4',
				170 => 'Zen 4',
				171 => 'Zen 4',
				172 => 'Zen 4',
				173 => 'Zen 4',
				174 => 'Zen 4',
				175 => 'Zen 4',
				),
			26 => array(
				// 1Ah for Zen 5 so far
				// Auto-populated below due to wide span
				),
			);

		for($i = 0; $i < 128; $i++)
		{
			// Based on GCC patch and other Linux patches
			$amd_map[26][$i] = 'Zen 5';
		}

		$intel_map = array(
			5 => array(
				9 => 'Quark',
				10 => 'Quark',
				),
			6 => array(
				2 => 'Nehalem',
				7 => 'Katmai',
				8 => 'Coppermine',
				11 => 'Tualatin',
				14 => 'Yonah',
				15 => 'Merom',
				23 => 'Penryn',
				29 => 'Penryn',
				26 => 'Nehalem',
				30 => 'Nehalem',
				37 => 'Arrandale',
				46 => 'Nehalem',
				44 => 'Westmere',
				47 => 'Westmere',
				28 => 'Bonnell',
				38 => 'Bonnell',
				39 => 'Saltwell',
				42 => 'Sandy Bridge',
				45 => 'Sandy Bridge',
				53 => 'Saltwell',
				54 => 'Saltwell',
				55 => 'Bay Trail',
				58 => 'Ivy Bridge',
				62 => 'Ivy Bridge',
				60 => 'Haswell',
				61 => 'Broadwell',
				63 => 'Haswell',
				69 => 'Haswell',
				70 => 'Haswell',
				71 => 'Broadwell',
				74 => 'Silvermont',
				76 => 'Airmont',
				77 => 'Silvermont',
				78 => 'Skylake',
				79 => 'Broadwell',
				85 => 'Cascade Lake',
				86 => 'Broadwell',
				87 => 'Knights Landing',
				90 => 'Silvermont',
				92 => 'Goldmont',
				93 => 'Silvermont',
				94 => 'Skylake',
				95 => 'Goldmont',
				102 => 'Cannon Lake',
				106 => 'Ice Lake',
				108 => 'Ice Lake',
				122 => 'Gemini Lake',
				125 => 'Ice Lake',
				126 => 'Ice Lake',
				133 => 'Knights Mill',
				134 => 'Jacobsville',
				138 => 'Lakefield',
				140 => 'Tiger Lake',
				141 => 'Tiger Lake',
				142 => 'Kaby/Coffee/Whiskey Lake',
				143 => 'Sapphire Rapids',
				150 => 'Elkhart Lake',
				151 => 'Alder Lake',
				154 => 'Alder Lake',
				156 => 'Jasper Lake',
				157 => 'Ice Lake',
				158 => 'Kaby/Coffee/Whiskey Lake',
				165 => 'Comet Lake',
				166 => 'Comet Lake',
				167 => 'Rocket Lake',
				168 => 'Rocket Lake',
				170 => 'Meteor Lake',
				172 => 'Meteor Lake',
				173 => 'Granite Rapids',
				174 => 'Granite Rapids',
				175 => 'Sierra Forest',
				181 => 'Arrow Lake',
				182 => 'Grand Ridge',
				183 => 'Raptor Lake',
				186 => 'Raptor Lake',
				189 => 'Lunar Lake',
				190 => 'Alder Lake',
				191 => 'Raptor Lake',
				197 => 'Arrow Lake',
				198 => 'Arrow Lake',
				204 => 'Panther Lake',
				207 => 'Emerald Rapids',
				221 => 'Clearwater Forest',
				),
			15 => array(
				1 => 'Clarksfield',
				2 => 'Northwood',
				3 => 'Prescott',
				4 => 'Prescott',
				6 => 'Cedar Mill',
				),
			19 => array(
				1 => 'Diamond Rapids',
				),
			);

		$other_map = array(
			4 => array(
				7 => 'Elbrus', // ZHAOXIN
				),
			6 => array(
				15 => 'Centaur', // Centaur
				23 => 'Penryn',
				44 => 'Westmere', // sometimes lacks Intel in CPU string so former map fails
				),
			7 => array(
				59 => 'Lujiazui', // ZHAOXIN
				),
			24 => array(
				0 => 'Dhyana', // Hygon Dhyana first gen Zen AMD EPYC
				),
			);

		if(($cpu_string == null || strpos($cpu_string, 'AMD') !== false || strpos($cpu_string, ' Athlon') !== false) && isset($amd_map[$family][$model]))
		{
			return $amd_map[$family][$model];
		}
		if(($cpu_string == null || stripos($cpu_string, 'Intel') !== false) && isset($intel_map[$family][$model]))
		{
			return $intel_map[$family][$model];
		}
		if(isset($other_map[$family][$model]))
		{
			return $other_map[$family][$model];
		}
		if($family != null && $model != null)
		{
			return 'Family ' . $family . ', Model ' . $model;
		}
	}
	public static function get_cpu_feature_constant($constant)
	{
		$features = self::get_cpu_feature_constants();

		return isset($features[$constant]) ? $features[$constant] : -1;
	}
	public static function read_cpuinfo_line($key, $from_start = true)
	{
		$line = false;
		$key .= "\t";

		if(isset(phodevi::$vfs->cpuinfo) && ($from_start && ($key_pos = strpos(phodevi::$vfs->cpuinfo, PHP_EOL . $key)) !== false) || ($key_pos = strrpos(phodevi::$vfs->cpuinfo, PHP_EOL . $key)) !== false)
		{
			$line = substr(phodevi::$vfs->cpuinfo, $key_pos);
			$line = substr($line, strpos($line, ':') + 1);
			$line = trim(substr($line, 0, strpos($line, PHP_EOL)));
		}

		return $line;
	}
	public static function read_cpuinfo_line_multi($key)
	{
		$lines = array();
		$key .= "\t";
		$offset = 0;
		while(isset(phodevi::$vfs->cpuinfo) && ($key_pos = strpos(phodevi::$vfs->cpuinfo, PHP_EOL . $key, $offset)) !== false)
		{
			$l = substr(phodevi::$vfs->cpuinfo, $key_pos);
			$l = substr($l, strpos($l, ':') + 1);
			$lines[] = trim(substr($l, 0, strpos($l, PHP_EOL)));
			$offset = $key_pos + 1;
		}

		return $lines;
	}
	public static function set_cpu_feature_flags()
	{
		$flags = explode(' ', self::read_cpuinfo_line('flags'));

		self::$cpu_flags = 0;
		foreach(self::get_cpu_feature_constants() as $feature => $value)
		{
			if(in_array($feature, $flags))
			{
				// The feature is supported on the CPU
				self::$cpu_flags |= $value;
			}
		}
	}
	public static function get_cpu_flags()
	{
		if(self::$cpu_flags === -1)
		{
			self::set_cpu_feature_flags();
		}

		return self::$cpu_flags;
	}
	public static function instruction_set_extensions()
	{
		$constants = self::get_cpu_feature_constants();
		self::set_cpu_feature_flags();
		$cpu_flags = self::get_cpu_flags();
		$extension_string = null;

		foreach($constants as $feature => $value)
		{
			// find maximum SSE version
			if(substr($feature, 0, 3) == 'sse' && ($cpu_flags & $value))
			{
				$extension_string = 'SSE ' . str_replace('_', '.', substr($feature, 3));
			}
		}

		// Check for other instruction sets
		foreach(array('avx512_vnni', 'avx512cd', 'avx2', 'avx', 'xop', 'fma3', 'fma4', 'rdrand', 'fsgsbase', 'amx_tile') as $instruction_set)
		{
			if(($cpu_flags & self::get_cpu_feature_constant($instruction_set)))
			{
				$extension_string .= ($extension_string != null ? ' + ' : null) . strtoupper($instruction_set);
			}
		}

		return $extension_string;
	}
	public static function virtualization_technology()
	{
		$constants = self::get_cpu_feature_constants();
		$cpu_flags = self::get_cpu_flags();
		$virtualitzation_technology = false;

		if(($cpu_flags & self::get_cpu_feature_constant('vmx')))
		{
			$virtualitzation_technology = 'VT-x';
		}
		else if(($cpu_flags & self::get_cpu_feature_constant('svm')))
		{
			$virtualitzation_technology = 'AMD-V';
		}

		return $virtualitzation_technology;
	}
	public static function lscpu_l2_cache()
	{
		$l2_cache = false;

		if(isset(phodevi::$vfs->lscpu) && ($t = strpos(phodevi::$vfs->lscpu, 'L2 cache:')))
		{
			$lscpu = substr(phodevi::$vfs->lscpu, $t + strlen('L2 cache:') + 1);
			$lscpu = substr($lscpu, 0, strpos($lscpu, PHP_EOL));
			$l2_cache = trim($lscpu);
		}

		return $l2_cache;
	}
	public static function cpuinfo_core_count()
	{
		$core_count = self::read_cpuinfo_line('cpu cores');

		if($core_count == false || !is_numeric($core_count))
		{
			$core_count = self::read_cpuinfo_line('core id', false);

			if(is_numeric($core_count))
			{
				// cpuinfo 'core id' begins counting at 0
				$core_count += 1;
			}
		}

		if($core_count == false || !is_numeric($core_count))
		{
			$core_count = self::cpuinfo_thread_count();
		}
		else
		{
			$physical_id = self::read_cpuinfo_line('physical id', false);
			$physical_id = (empty($physical_id) || !is_numeric($physical_id) ? 0 : $physical_id) + 1;
			$core_count = $physical_id * $core_count;
		}

		if(pts_client::executable_in_path('lscpu'))
		{
			$lscpu = trim(shell_exec('lscpu -p | grep -E -v \'^#\' | sort -u -t, -k 2,4 | wc -l'));
			if(is_numeric($lscpu) && $lscpu > $core_count)
			{
				$core_count = $lscpu;
			}
		}

		return $core_count;
	}
	public static function cpuinfo_thread_count()
	{
		$thread_count = count(self::read_cpuinfo_line_multi('processor', false));

		return $thread_count;
	}
	public static function cpuinfo_cache_size()
	{
		// CPU cache size in KB
		$cache_size = self::read_cpuinfo_line('cache size');

		if(substr($cache_size, -3) == ' KB')
		{
			$cache_size = substr($cache_size, 0, -3);
		}
		else
		{
			$cache_size = null;
		}

		return $cache_size;
	}
	public static function cpu_cache_size_string()
	{
		$cache_size = phodevi::read_property('cpu', 'cache-size');
		if($cache_size > 0.1)
		{
			$cache_size .= ' MB';
		}

		return $cache_size;
	}
}

?>
