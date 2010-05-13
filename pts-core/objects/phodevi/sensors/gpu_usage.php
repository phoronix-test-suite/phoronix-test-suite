<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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

class gpu_usage implements phodevi_sensor
{
	static $probe_ati_overdrive = false;
	static $probe_radeon_fences = false;

	public static function get_type()
	{
		return "gpu";
	}
	public static function get_sensor()
	{
		return "usage";
	}
	public static function get_unit()
	{
		if(self::$probe_ati_overdrive)
		{
			$unit = "Megahertz";
		}
		else if(self::$probe_radeon_fences)
		{
			$unit = "Fences/s";
		}

		return $unit;
	}
	public static function support_check()
	{
		if(IS_ATI_GRAPHICS && IS_LINUX)
		{
			$gpu_usage = self::ati_overdrive_core_usage();

			if(is_numeric($gpu_usage))
			{
				self::$probe_ati_overdrive = true;
				return true;
			}
		}
		else if(IS_MESA_GRAPHICS && is_readable("/sys/kernel/debug/dri/0/radeon_fence_info"))
		{
			$fence_speed = self::radeon_fence_speed();

			if(is_numeric($fence_speed) && $fence_speed >= 0)
			{
				self::$probe_radeon_fences = true;
				return true;
			}
		}

		return false;
	}
	public static function read_sensor()
	{
		if(self::$probe_ati_overdrive)
		{
			return self::ati_overdrive_core_usage();
		}
		else if(self::$probe_radeon_fences)
		{
			return self::radeon_fence_speed();
		}
	}
	public static function ati_overdrive_core_usage()
	{
		return phodevi_linux_parser::read_ati_overdrive("GPUload");
	}
	public static function radeon_fence_speed()
	{
		// Determine GPU usage
		$fence_speed = -1;

		/*
			Last signaled fence 0x00AF9AF1
			Last emited fence ffff8800ac0e2080 with 0x00AF9AF1
		*/

		$fence_info = file_get_contents("/sys/kernel/debug/dri/0/radeon_fence_info");
		$start_signaled_fence = substr($fence_info, strpos("Last signaled fence", $fence_info));
		$start_signaled_fence = substr($start_signaled_fence, 0, strpos($start_signaled_fence, "\n"));
		$start_signaled_fence = substr($start_signaled_fence, strrpos($start_signaled_fence, ' '));

		sleep(1);

		$fence_info = file_get_contents("/sys/kernel/debug/dri/0/radeon_fence_info");
		$end_signaled_fence = substr($fence_info, strpos("Last signaled fence", $fence_info));
		$end_signaled_fence = substr($end_signaled_fence, 0, strpos($end_signaled_fence, "\n"));
		$end_signaled_fence = substr($end_signaled_fence, strrpos($end_signaled_fence, ' '));

		$fence_speed = hexdec($end_signaled_fence) - hexdec($start_signaled_fence);

		return $fence_speed;
	}
}

?>
