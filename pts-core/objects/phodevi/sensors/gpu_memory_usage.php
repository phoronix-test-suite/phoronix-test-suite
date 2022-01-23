<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2017 - 2019, Phoronix Media
	Copyright (C) 2017 - 2019, Michael Larabel

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

class gpu_memory_usage extends phodevi_sensor
{
	const SENSOR_TYPE = 'gpu';
	const SENSOR_SENSES = 'memory-usage';
	const SENSOR_UNIT = 'Megabytes';

	public function read_sensor()
	{
		$mem_usage = -1;

		if(($nvidia_smi = pts_client::executable_in_path('nvidia-smi')))
		{
			$smi_output = shell_exec(escapeshellarg($nvidia_smi) . ' -q -d MEMORY');
			$mem = strpos($smi_output, 'Used');
			if($mem !== false)
			{
				$mem = substr($smi_output, strpos($smi_output, ':', $mem) + 1);
				$mem = trim(substr($mem, 0, strpos($mem, 'MiB')));

				if(is_numeric($mem) && $mem > 0)
				{
					$mem_usage = $mem;
				}
			}
		}
		else if(phodevi::is_linux() && is_file('/sys/class/drm/card0/device/mem_info_vram_used'))
		{
			// AMDGPU Linux driver at least supports this interface
			$mem_info_vram_used = pts_file_io::file_get_contents('/sys/class/drm/card0/device/mem_info_vram_used');
			if(is_numeric($mem_info_vram_used) && $mem_info_vram_used > 1000000)
			{
				$mem_usage = floor($mem_info_vram_used / 1000000);
			}
		}
		else if(phodevi::is_linux() && is_file('/sys/class/drm/card0/device/mem_busy_percent'))
		{
			$mem_usage = pts_file_io::file_get_contents('/sys/class/drm/card0/device/mem_busy_percent');
		}

		return $mem_usage;
	}
}

?>
