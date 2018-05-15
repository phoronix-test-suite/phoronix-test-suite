<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2018, Phoronix Media
	Copyright (C) 2008 - 2018, Michael Larabel
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
			'microcode-version' => new phodevi_device_property('cpu_microcode_version', phodevi::std_caching),
			'cache-size' => new phodevi_device_property('cpu_cache_size', phodevi::smart_caching),
			'cache-size-string' => new phodevi_device_property('cpu_cache_size_string', phodevi::smart_caching)
			);
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
			$model = str_replace($freq . 'GHz', null, $model); // we'll replace it if it's already in the string
			$model .= ' @ ' . $freq . 'GHz';
		}

		return $model;
	}
	public static function cpu_core_count()
	{
		$info = null;

		if(getenv('PTS_NPROC') && is_numeric(getenv('PTS_NPROC')))
		{
			$info = getenv('PTS_NPROC');
		}
		else if(getenv('NUMBER_OF_PROCESSORS') && is_numeric(getenv('NUMBER_OF_PROCESSORS')))
		{
			// Should be used by Windows they have NUMBER_OF_PROCESSORS set and use this as an easy way to override CPUs exposed
			$info = getenv('NUMBER_OF_PROCESSORS');
		}
		else if(phodevi::is_linux())
		{
			if(is_file('/sys/devices/system/cpu/online'))
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
		else if(phodevi::is_macosx())
		{
			$info = intval(phodevi_bsd_parser::read_sysctl(array('hw.ncpu')));

			if(empty($info))
			{
				$info = phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'TotalNumberOfCores');
			}
		}
		else if(phodevi::is_windows())
		{
			// Should be hit by the first NUMBER_OF_PROCESSORS env check...
			//$info = getenv('NUMBER_OF_PROCESSORS');
		}

		if($info == null && isset(phodevi::$vfs->cpuinfo))
		{
			$info = self::cpuinfo_core_count();
		}

		return (is_numeric($info) && $info > 0 ? $info : 1);
	}
	public static function cpu_physical_core_count()
	{
		$physical_cores = null;

		if(phodevi::is_linux())
		{
			$physical_cores = phodevi_cpu::cpuinfo_core_count();
		}
		else if(phodevi::is_bsd())
		{
			// hw.cpu_topology_core_ids works at least on DragonFly BSD
			$physical_cores = intval(phodevi_bsd_parser::read_sysctl(array('hw.cpu_topology_core_ids')));
		}
		else if(phodevi::is_macosx())
		{
			$physical_cores = intval(phodevi_bsd_parser::read_sysctl(array('hw.physicalcpu')));
		}
		else if(phodevi::is_windows())
		{
			$physical_cores = phodevi_windows_parser::get_wmi_object('Win32_Processor', 'NumberOfCores');
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
			$cache_size = self::cpuinfo_cache_size();
		}
		else if(phodevi::is_macosx())
		{
			$cache_size = phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'L3Cache');

			if(strpos($cache_size, ' MB'))
			{
				$cache_size = substr($cache_size, 0, strpos($cache_size, ' ')) * 1024;
			}
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

		return trim($scaling_governor);
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
			if($info == null && isset(phodevi::$vfs->cpuinfo)) // fall back for those without cpufreq
			{
				$cpu_mhz = self::read_cpuinfo_line('cpu MHz');
				$info = $cpu_mhz / 1000;

				if(empty($info))
				{
					$cpu_mhz = self::read_cpuinfo_line('clock');
					$info = $cpu_mhz / 1000;
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

			$cpu_strings = phodevi_linux_parser::read_cpuinfo(array('model name', 'Processor', 'cpu', 'cpu model'));
			$cpu_strings_unique = array_unique($cpu_strings);

			if($physical_cpu_count == 1 || empty($physical_cpu_count))
			{
				// Just one processor
				if(isset($cpu_strings[0]) && ($cut = strpos($cpu_strings[0], ' (')) !== false)
				{
					$cpu_strings[0] = substr($cpu_strings[0], 0, $cut);
				}

				$info = isset($cpu_strings[0]) ? $cpu_strings[0] : null;

				if(strpos($info, 'ARM') !== false)
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
		else if(phodevi::is_macosx())
		{
			$info = phodevi_osx_parser::read_osx_system_profiler('SPHardwareDataType', 'ProcessorName');
		}
		else if(phodevi::is_windows())
		{
			$info = phodevi_windows_parser::get_wmi_object('win32_processor', 'Name');

			if(!$info)
			{
				$info = getenv('PROCESSOR_IDENTIFIER');
			}
		}

		if(empty($info))
		{
			if(phodevi::is_linux())
			{
				if(strpos(phodevi::$vfs->dmesg, 'thunderx') !== false)
				{
					// Haven't found a better way to detect ThunderX as not exposed via cpuinfo, etc
					$info = 'Cavium ThunderX';
				}
				$isa = phodevi_linux_parser::read_cpuinfo('isa');
				$uarch = phodevi_linux_parser::read_cpuinfo('uarch');
				if(!empty($isa))
				{
					$isa = array_pop($isa);
				}
				if(!empty($uarch))
				{
					$isa = array_pop($uarch);
				}

				if(stripos($isa, 'rv') !== false && strpos($uarch, 'sifive') !== false)
				{
					$info = 'SiFive RISC-V';
				}
				else if(!empty($isa))
				{
					$info = $isa;
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
			if(strpos($info, 'Pentium') !== false && strpos($info, 'Intel') === false)
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
			'sse5' => (1 << 6), // SSE 5
			'avx' => (1 << 7), // AVX
			'avx2' => (1 << 8), // AVX2
			'aes' => (1 << 9), // AES
			'epb' => (1 << 10), // EPB
			'svm' => (1 << 11), // AMD SVM (Virtualization)
			'vmx' => (1 << 12), // Intel Virtualization
			'xop' => (1 << 13), // AMD XOP Instruction Set
			'fma3' => (1 << 14), // FMA3 Instruction Set
			'fma4' => (1 << 15), // FMA4 Instruction Set
			'rdrand' => (1 << 16), // Intel Bull Mountain RDRAND - Ivy Bridge
			'fsgsbase' => (1 << 17), // FSGSBASE - Ivy Bridge AVX
			'bmi2' => (1 << 18), // Intel Haswell has BMI2
			'avx512cd' => (1 << 19) // AVX-512
			);
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
		foreach(array('avx512cd', 'avx2', 'avx', 'xop', 'fma3', 'fma4', 'rdrand', 'fsgsbase') as $instruction_set)
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
		if(pts_client::executable_in_path('lscpu'))
		{
			$lscpu = trim(shell_exec('lscpu -p | egrep -v \'^#\' | sort -u -t, -k 2,4 | wc -l'));
		}

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
		if(is_numeric($lscpu) && $lscpu > $core_count)
		{
			$core_count = $lscpu;
		}

		return $core_count;
	}
	public static function cpuinfo_thread_count()
	{
		$thread_count = self::read_cpuinfo_line('processor', false);

		if(is_numeric($thread_count))
		{
			// cpuinfo 'processor' begins counting at 0
			$thread_count += 1;
		}

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
		if($cache_size > 1)
		{
			$cache_size .= ' KB';
		}

		return $cache_size;
	}
}

?>
