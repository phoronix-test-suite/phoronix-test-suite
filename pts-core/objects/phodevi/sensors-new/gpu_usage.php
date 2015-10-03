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

//TODO test this sensor on different devices

class gpu_usage extends phodevi_sensor
{
	const SENSOR_TYPE = 'gpu';
	const SENSOR_SENSES = 'usage';

	private $probe_ati_overdrive = false;
	private $probe_radeontop = false;
	private $probe_radeon_fences = false;
	private $probe_intel_commands = false;
	private $probe_nvidia_smi = false;
	private $probe_nvidia_settings = false;

	public static function get_unit()
	{
		if(self::$probe_ati_overdrive || self::$probe_nvidia_smi || self::$probe_nvidia_settings ||
			self::$probe_radeontop)
		{
			$unit = 'Percent';
		}
		else if(self::$probe_radeon_fences)
		{
			$unit = 'Fences/s';
		}
		else if(self::$probe_intel_commands)
		{
			$unit = 'Commands/s';
		}

		return $unit;
	}

	//TODO rewrite support checking if possible
	public function support_check()
	{
		// temporarily disabled as it needs fixing

//		if(phodevi::is_ati_graphics() && phodevi::is_linux())
//		{
//			$gpu_usage = self::ati_overdrive_core_usage();
//
//			if(is_numeric($gpu_usage))
//			{
//				self::$probe_ati_overdrive = true;
//				return true;
//			}
//		}
//		else if(phodevi::is_mesa_graphics())
//		{
//			if(pts_client::executable_in_path('radeontop'))
//			{
//				$test = self::radeontop_gpu_usage();
//
//				if(is_numeric($test) && $test >= 0)
//				{
//					self::$probe_radeontop = true;
//					return true;
//				}
//			}
//			else if(is_readable('/sys/kernel/debug/dri/0/radeon_fence_info'))
//			{
//				$fence_speed = self::radeon_fence_speed();
//
//				if(is_numeric($fence_speed) && $fence_speed >= 0)
//				{
//					self::$probe_radeon_fences = true;
//					return true;
//				}
//			}
//			else if(is_readable('/sys/kernel/debug/dri/0/i915_gem_seqno'))
//			{
//				$commands = self::intel_command_speed();
//				if(is_numeric($commands) && $commands > 0)
//				{
//					self::$probe_intel_commands = true;
//					return true;
//				}
//			}
//		}
//		else if(phodevi::is_nvidia_graphics())
//		{
//			$util = self::read_nvidia_settings_gpu_utilization();
//
//			if($util !== false)
//			{
//				self::$probe_nvidia_settings = true;
//				return true;
//			}
//			else if(pts_client::executable_in_path('nvidia-smi'))
//			{
//				$usage = self::nvidia_core_usage();
//
//				if(is_numeric($usage) && $usage >= 0 && $usage <= 100)
//				{
//					self::$probe_nvidia_smi = true;
//					return true;
//				}
//			}
//		}

		return false;
	}

	public function read_sensor()
	{
		if(self::$probe_ati_overdrive)
		{
			return self::ati_overdrive_core_usage();
		}
		else if(self::$probe_nvidia_settings)
		{
			return self::read_nvidia_settings_gpu_utilization();
		}
		else if(self::$probe_nvidia_smi)
		{
			return self::nvidia_core_usage();
		}
		else if(self::$probe_radeontop)
		{
			return self::radeontop_gpu_usage();
		}
		else if(self::$probe_radeon_fences)
		{
			return self::radeon_fence_speed();
		}
		else if(self::$probe_intel_commands)
		{
			return self::intel_command_speed();
		}
	}

	public static function read_nvidia_settings_gpu_utilization()
	{
		$util = phodevi_parser::read_nvidia_extension('GPUUtilization');

		if(is_numeric($util) && $util >= 0 && $util <= 100)
		{
			return $util;
		}
		else
		{
			if(($x = stripos($util, 'graphics=')) !== false)
			{
				$util = substr($util, ($x + 9));
				$util = substr($util, 0, strpos($util, ','));

				if(is_numeric($util) && $util >= 0 && $util <= 100)
				{
					return $util;
				}
			}
		}

		return false;
	}

	public static function ati_overdrive_core_usage()
	{
		return phodevi_linux_parser::read_ati_overdrive('GPUload');
	}

	public static function nvidia_core_usage()
	{
		$nvidia_smi = shell_exec('nvidia-smi -a');

		$util = substr($nvidia_smi, strpos($nvidia_smi, 'Utilization'));
		$util = substr($util, strpos($util, 'GPU'));
		$util = substr($util, strpos($util, ':') + 1);
		$util = trim(substr($util, 0, strpos($util, '%')));

		return $util;
	}

	public static function radeon_fence_speed()
	{
		// Determine GPU usage
		$fence_speed = -1;

		/*
			Last signaled fence 0x00AF9AF1
			Last emited fence ffff8800ac0e2080 with 0x00AF9AF1
		*/

		$fence_info = file_get_contents('/sys/kernel/debug/dri/0/radeon_fence_info');
		$start_signaled_fence = substr($fence_info, strpos('Last signaled fence', $fence_info));
		$start_signaled_fence = substr($start_signaled_fence, 0, strpos($start_signaled_fence, "\n"));
		$start_signaled_fence = substr($start_signaled_fence, strrpos($start_signaled_fence, ' '));

		sleep(1);

		$fence_info = file_get_contents('/sys/kernel/debug/dri/0/radeon_fence_info');
		$end_signaled_fence = substr($fence_info, strpos('Last signaled fence', $fence_info));
		$end_signaled_fence = substr($end_signaled_fence, 0, strpos($end_signaled_fence, "\n"));
		$end_signaled_fence = substr($end_signaled_fence, strrpos($end_signaled_fence, ' '));

		$fence_speed = hexdec($end_signaled_fence) - hexdec($start_signaled_fence);

		return $fence_speed;
	}

	protected static function intel_current_sequence_count()
	{
		$count = 0;
		$i915_gem_seqno = file_get_contents('/sys/kernel/debug/dri/0/i915_gem_seqno');
		$current_sequence = strpos($i915_gem_seqno, 'Current sequence (render ring): ');

		if($current_sequence !== false)
		{
			$current_sequence = substr($i915_gem_seqno, $current_sequence + 32);
			$current_sequence = substr($current_sequence, 0, strpos($current_sequence, PHP_EOL));

			if(is_numeric($current_sequence))
			{
				$count = $current_sequence;
			}
		}

		return $count;
	}

	public static function intel_command_speed()
	{
		// Determine GPU usage
		$first_read = self::intel_current_sequence_count();
		sleep(1);
		$second_read = self::intel_current_sequence_count();

		return $second_read - $first_read;
	}

	public static function radeontop_gpu_usage()
	{
		$out = shell_exec('radeontop -d - -l 1');

		$pos = strpos($out, 'gpu');
		if ($pos === false)
			return -1;

		$out = substr($out, $pos + 4);
		$out = trim(substr($out, 0, strpos($out, '%')));

		return $out;
	}

}

?>
