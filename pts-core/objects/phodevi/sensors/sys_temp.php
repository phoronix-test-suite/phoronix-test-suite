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

class sys_temp extends phodevi_sensor
{
	const SENSOR_TYPE = 'sys';
	const SENSOR_SENSES = 'temp';
	const SENSOR_UNIT = 'Celsius';

	public function read_sensor()
	{
		$sys_temp = -1;

		if(phodevi::is_linux())
		{
			$sys_temp = $this->sys_temp_linux();
		}
		elseif(phodevi::is_bsd())
		{
			$sys_temp = $this->sys_temp_bsd();
		}

		return $sys_temp;
	}

	private function sys_temp_linux()
	{
		$temp_c = -1;

		
		$raw_temp = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/device/temp3_input', 'POSITIVE_NUMERIC', array('name' => '!coretemp,!radeon,!nouveau,!nvme,!amdgpu'));

		if($raw_temp == -1)
		{
			$raw_temp = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/device/temp2_input', 'POSITIVE_NUMERIC', array('name' => '!coretemp,!radeon,!nouveau,!nvme,!amdgpu'));
		}

		if($raw_temp == -1)
		{
			$raw_temp = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/device/temp1_input', 'POSITIVE_NUMERIC', array('name' => '!coretemp,!radeon,!nouveau,!nvme,!amdgpu'));
		}

		if($raw_temp == -1)
		{
			//$raw_temp = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/temp1_input', 'POSITIVE_NUMERIC');
		}

		if($raw_temp != -1)
		{
			if($raw_temp > 1000)
			{
				$raw_temp = $raw_temp / 1000;
			}

			$temp_c = pts_math::set_precision($raw_temp, 2);
		}

		if($temp_c == -1)
		{
			$acpi = phodevi_linux_parser::read_acpi(array(
				'/thermal_zone/THM1/temperature',
				'/thermal_zone/TZ00/temperature',
				'/thermal_zone/TZ01/temperature'), 'temperature');

			if(($end = strpos($acpi, ' ')) > 0)
			{
				$temp_c = substr($acpi, 0, $end);
			}
		}

		if($temp_c == -1)
		{
			$sensors = phodevi_linux_parser::read_sensors(array('Sys Temp', 'Board Temp'));

			if($sensors != false && is_numeric($sensors))
			{
				$temp_c = $sensors;
			}
		}

		if($temp_c == -1 && is_file('/sys/class/thermal/thermal_zone0/temp'))
		{
			$temp_c = pts_file_io::file_get_contents('/sys/class/thermal/thermal_zone0/temp');

			if($temp_c > 1000)
			{
				$temp_c = pts_math::set_precision(($temp_c / 1000), 1);
			}
		}

		if(pts_client::executable_in_path('ipmitool'))
		{
			$ipmi = phodevi_linux_parser::read_ipmitool_sensor(array('MB Temp'));

			if($ipmi > 0 && is_numeric($ipmi))
			{
				$temp_c = $ipmi;
			}
		}

		return $temp_c;
	}

	private function sys_temp_bsd()
	{
		$temp_c = -1;
		$acpi = phodevi_bsd_parser::read_sysctl(array('hw.sensors.acpi_tz1.temp0', 'hw.acpi.thermal.tz1.temperature'));

		if(($end = strpos($acpi, ' degC')) > 0 || ($end = strpos($acpi, 'C')) > 0)
		{
			$acpi = substr($acpi, 0, $end);

			if(is_numeric($acpi))
			{
				$temp_c = $acpi;
			}
		}

		return $temp_c;
	}
}

?>
