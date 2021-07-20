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

//TODO refactor and fix returning of two values
class gpu_freq extends phodevi_sensor
{
	const SENSOR_TYPE = 'gpu';
	const SENSOR_SENSES = 'freq';
	const SENSOR_UNIT = 'Megahertz';

	public function read_sensor()
	{
		// Graphics processor real/current frequency
		$show_memory = false;
		$core_freq = 0;
		$mem_freq = 0;

		if(phodevi::is_nvidia_graphics()) // NVIDIA GPU
		{
			$nv_freq = phodevi_parser::read_nvidia_extension('GPUCurrentClockFreqs');

			$nv_freq = pts_strings::comma_explode($nv_freq);
			$core_freq = isset($nv_freq[0]) ? $nv_freq[0] : 0;
			$mem_freq = isset($nv_freq[1]) ? $nv_freq[1] : 0;
		}
		else if(phodevi::is_linux())
		{
			if(is_readable('/sys/class/drm/card0/device/pp_dpm_sclk'))
			{
				$pp = PHP_EOL . file_get_contents('/sys/class/drm/card0/device/pp_dpm_sclk');
				$pp = substr($pp, 0, strpos($pp, '*'));
				$pp = substr($pp, strrpos($pp, PHP_EOL));
				if(($x = strpos($pp, ': ')) !== false)
				{
					$pp = substr($pp, $x + 2);
				}
				$pp = trim(str_replace(array('*', 'Mhz'), '', $pp));
				if(is_numeric($pp))
				{
					$core_freq = $pp;
					if(is_readable('/sys/class/drm/card0/device/pp_dpm_mclk'))
					{
						$pp = PHP_EOL . file_get_contents('/sys/class/drm/card0/device/pp_dpm_mclk');
						$pp = substr($pp, 0, strpos($pp, '*'));
						$pp = substr($pp, strrpos($pp, PHP_EOL));
						if(($x = strpos($pp, ': ')) !== false)
						{
							$pp = substr($pp, $x + 2);
						}
						$pp = trim(str_replace(array('*', 'Mhz'), '', $pp));
						if(is_numeric($pp))
						{
							$mem_freq = $pp;
						}
					}
				}
			}
			else if(isset(phodevi::$vfs->radeon_pm_info))
			{
				// radeon_pm_info should be present with Linux 2.6.34+
				foreach(pts_strings::trim_explode("\n", phodevi::$vfs->radeon_pm_info) as $pm_line)
				{
					$pm_line = pts_strings::colon_explode($pm_line);

					if(isset($pm_line[1]))
					{
						list($descriptor, $value) = $pm_line;
					}
					else
					{
						continue;
					}

					switch($descriptor)
					{
						case 'current engine clock':
							$core_freq = pts_arrays::first_element(explode(' ', $value)) / 1000;
							break;
						case 'current memory clock':
							$mem_freq = pts_arrays::first_element(explode(' ', $value)) / 1000;
							break;
					}
				}

				if($core_freq == null && ($x = strpos(phodevi::$vfs->radeon_pm_info, 'sclk: ')))
				{
					$x = substr(phodevi::$vfs->radeon_pm_info, ($x + strlen('sclk: ')));
					$x = substr($x, 0, strpos($x, ' '));

					if(is_numeric($x))
					{
						if($x > 1000)
						{
							$x = $x / 100;
						}

						$core_freq = $x;
					}
				}
				if($mem_freq == null && ($x = strpos(phodevi::$vfs->radeon_pm_info, 'mclk: ')))
				{
					$x = substr(phodevi::$vfs->radeon_pm_info, ($x + strlen('mclk: ')));
					$x = substr($x, 0, strpos($x, ' '));

					if(is_numeric($x))
					{
						if($x > 1000)
						{
							$x = $x / 100;
						}

						$mem_freq = $x;
					}
				}
			}
			else if(is_file('/sys/class/drm/card0/gt_cur_freq_mhz'))
			{
				$gt_cur_freq_mhz = pts_file_io::file_get_contents('/sys/class/drm/card0/gt_cur_freq_mhz');
				if($gt_cur_freq_mhz > 2)
				{
					$core_freq = $gt_cur_freq_mhz;
				}
			}
			else if(is_file('/sys/class/drm/card0/device/performance_level'))
			{
				$performance_level = pts_file_io::file_get_contents('/sys/class/drm/card0/device/performance_level');
				$performance_level = explode(' ', $performance_level);

				$core_string = array_search('core', $performance_level);
				if($core_string !== false && isset($performance_level[($core_string + 1)]))
				{
					$core_string = str_ireplace('MHz', '', $performance_level[($core_string + 1)]);
					if(is_numeric($core_string) && $core_string > $core_freq)
					{
						$core_freq = $core_string;
					}
				}

				$mem_string = array_search('memory', $performance_level);
				if($mem_string !== false && isset($performance_level[($mem_string + 1)]))
				{
					$mem_string = str_ireplace('MHz', '', $performance_level[($mem_string + 1)]);
					if(is_numeric($mem_string) && $mem_string > $mem_freq)
					{
						$mem_freq = $mem_string;
					}
				}
			}
			else if(isset(phodevi::$vfs->i915_cur_delayinfo))
			{
				$i915_cur_delayinfo = phodevi::$vfs->i915_cur_delayinfo;
				$cagf = strpos($i915_cur_delayinfo, 'CAGF: ');

				if($cagf !== false)
				{
					$cagf_mhz = substr($i915_cur_delayinfo, $cagf + 6);
					$cagf_mhz = substr($cagf_mhz, 0, strpos($cagf_mhz, 'MHz'));

					if(is_numeric($cagf_mhz))
					{
						$core_freq = $cagf_mhz;
					}
				}
			}
		}

		if(!is_numeric($core_freq))
		{
			$core_freq = 0;
		}
		if(!is_numeric($mem_freq))
		{
			$mem_freq = 0;
		}

		if($core_freq == 0 && $mem_freq == 0)
		{
			$show_memory = false;
			$core_freq = -1;
		}

		return $show_memory ? array($core_freq, $mem_freq) : $core_freq;
	}
}

?>
