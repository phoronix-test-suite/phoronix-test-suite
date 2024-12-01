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

class memory_temp extends phodevi_sensor
{
	const SENSOR_TYPE = 'memory';
	const SENSOR_SENSES = 'temp';
	const SENSOR_UNIT = 'Celsius';

	public function read_sensor()
	{
		// Report memory temperature
		$temp_c = -1;

		if(pts_client::executable_in_path('ipmitool'))
		{
			$ipmi = phodevi_linux_parser::read_ipmitool_sensor(array('DIMM_MOSFET_1', 'DIMM_MOSFET_2', 'DDR4_A1_Temp', 'DIMMA~F Temp'));

			if($ipmi > 0 && is_numeric($ipmi))
			{
				$temp_c = $ipmi;
			}
		}

		if($temp_c == -1)
		{
			$temp_c = phodevi_linux_parser::read_sysfs_node('/sys/class/thermal/thermal_zone*/temp', 'POSITIVE_NUMERIC', array('type' => 'ddr_thermal'));
			if($temp_c > 1000)
			{
				$temp_c = $temp_c / 1000;
			}
		}

		if($temp_c > 1000 || $temp_c < 9)
		{
			// Invalid data
			return -1;
		}

		return pts_math::set_precision($temp_c, 2);
	}

}

?>
