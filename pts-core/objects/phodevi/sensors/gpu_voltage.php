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

class gpu_voltage extends phodevi_sensor
{
	const SENSOR_TYPE = 'gpu';
	const SENSOR_SENSES = 'voltage';
	const SENSOR_UNIT = 'Millivolts';

	public function read_sensor()
	{
		$sensor = -1;

		// TODO XXX: Nouveau driver exposes GPU voltage on at least some cards via performance_level
		if(is_file('/sys/class/drm/card0/device/hwmon/hwmon1/in0_label') && pts_file_io::file_get_contents('/sys/class/drm/card0/device/hwmon/hwmon1/in0_label') == 'vddgfx' && is_file('/sys/class/drm/card0/device/hwmon/hwmon1/in0_input'))
		{
			$sensor = pts_file_io::file_get_contents('/sys/class/drm/card0/device/hwmon/hwmon1/in0_input');
			if(!is_numeric($sensor))
			{
				$sensor = -1;
			}
		}
		else if(is_file('/sys/class/drm/card0/device/hwmon/hwmon0/in0_label') && pts_file_io::file_get_contents('/sys/class/drm/card0/device/hwmon/hwmon0/in0_label') == 'vddgfx' && is_file('/sys/class/drm/card0/device/hwmon/hwmon0/in0_input'))
		{
			$sensor = pts_file_io::file_get_contents('/sys/class/drm/card0/device/hwmon/hwmon0/in0_input');
			if(!is_numeric($sensor))
			{
				$sensor = -1;
			}
		}
		else if(isset(phodevi::$vfs->radeon_pm_info))
		{
			// For Radeon power management it should be exposed on a line like:
			// voltage: 1140 mV
			if(($x = strpos(phodevi::$vfs->radeon_pm_info, 'voltage: ')) !== false)
			{
				$radeon_pm_info = substr(phodevi::$vfs->radeon_pm_info, ($x + 9));

				if(($x = stripos($radeon_pm_info, ' mV')) !== false)
				{
					$sensor = substr($radeon_pm_info, 0, $x);
				}
			}

			if($sensor == null && ($x = strpos(phodevi::$vfs->radeon_pm_info, 'vddc: ')))
			{
				$x = ltrim(substr(phodevi::$vfs->radeon_pm_info, ($x + strlen('vddc: '))));
				$x = substr($x, 0, strpos($x, ' '));

				if(is_numeric($x))
				{
					$sensor = $x;
				}
			}
		}

		return $sensor;
	}
}

?>
