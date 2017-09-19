<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016 - 2017, Phoronix Media
	Copyright (C) 2016 - 2017, Michael Larabel

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

class ambient_temp extends phodevi_sensor
{
	const SENSOR_TYPE = 'ambient';
	const SENSOR_SENSES = 'temp';
	const SENSOR_UNIT = 'Celsius';

	public function read_sensor()
	{
		if(pts_client::executable_in_path('temperv14'))
		{
			$temperv14 = trim(shell_exec('temperv14 -c 2>&1'));

			if(!empty($temperv14) && is_numeric($temperv14))
			{
				return $temperv14;
			}
		}
		if(pts_client::executable_in_path('ipmitool'))
		{
			$ipmi = phodevi_linux_parser::read_ipmitool_sensor(array('SYS_Air_Inlet', 'MB_Air_Inlet'));

			if($ipmi > 0 && is_numeric($ipmi))
			{
				return $ipmi;
			}
		}
	}
}

?>
