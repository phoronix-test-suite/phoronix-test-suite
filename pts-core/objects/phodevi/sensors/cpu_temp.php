<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2023, Phoronix Media
	Copyright (C) 2009 - 2023, Michael Larabel

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

//TODO add support for per-core monitoring

class cpu_temp extends phodevi_sensor
{
	const SENSOR_TYPE = 'cpu';
	const SENSOR_SENSES = 'temp';
	const SENSOR_UNIT = 'Celsius';

	
	public function read_sensor()
	{
		// Read the processor temperature
		$temp_c = -1;

		if(phodevi::is_bsd())
		{
			$temp_c = $this->cpu_temp_bsd();
		}
		else if(phodevi::is_macos())
		{
			$temp_c = $this->cpu_temp_macosx();
		}
		else if(phodevi::is_linux())
		{
			$temp_c = $this->cpu_temp_linux();
		}
		else if(phodevi::is_windows())
		{
			$temp_c = $this->cpu_temp_windows();
		}

		return $temp_c;
	}
	private function cpu_temp_macosx()
	{
		$temp_c = -1;

		if(pts_client::executable_in_path('osx-cpu-temp'))
		{
			$osx_cpu_temp = trim(shell_exec('osx-cpu-temp 2>&1'));
			if(($x = strpos($osx_cpu_temp, '°C')) !== false)
			{
				$osx_cpu_temp = substr($osx_cpu_temp, 0, $x);
				if(is_numeric($osx_cpu_temp) && $osx_cpu_temp > 0)
				{
					$temp_c = $osx_cpu_temp;
				}
			}
		}
		return $temp_c;
	}
	private static function cpu_temp_windows()
	{
		$output = trim(shell_exec('powershell -NoProfile (Get-WmiObject MSAcpi_ThermalZoneTemperature -Namespace "root/wmi").CurrentTemperature'));
		if(!empty($output) && is_numeric($output) && $output > 0)
		{
			// Convert to C
			$output = ($output - 2732) / 10;
			if($output > 1)
			{
				return $output;
			}
		}
			return -1;
	}
	private function cpu_temp_bsd()
	{
		$temp_c = -1;
		$cpu_temp = phodevi_bsd_parser::read_sysctl(array('hw.sensors.acpi_tz0.temp0', 'dev.cpu.0.temperature', 'hw.sensors.cpu0.temp0'));

		if($cpu_temp != false)
		{
			if(($end = strpos($cpu_temp, 'degC')) || ($end = strpos($cpu_temp, 'C')) > 0)
			{
				$cpu_temp = substr($cpu_temp, 0, $end);
			}
			$cpu_temp = trim($cpu_temp);

			if(is_numeric($cpu_temp))
			{
				$temp_c = $cpu_temp;
			}
		}
		else
		{
			$acpi = phodevi_bsd_parser::read_sysctl('hw.acpi.thermal.tz0.temperature');

			if(($end = strpos($acpi, 'C')) > 0)
			{
				$acpi = substr($acpi, 0, $end);
			}

			if(is_numeric($acpi))
			{
				$temp_c = $acpi;
			}
		}
		
		return $temp_c;
	}
	
	private function cpu_temp_linux()
	{
		$temp_c = -1;
		// Try hwmon interface
		$raw_temp = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/temp*_input', 'POSITIVE_NUMERIC', array('name' => 'coretemp'));

		if($raw_temp == -1)
		{
			$raw_temp = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/temp*_input', 'POSITIVE_NUMERIC', array('name' => 'k10temp'));
		}
		if($raw_temp == -1)
		{
			// Raspberry Pi's Broadcom SoC
			$raw_temp = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/temp*_input', 'POSITIVE_NUMERIC', array('temp1_label' => 'SoC Temperature'));
		}
		if($raw_temp == -1)
		{
			$raw_temp = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/temp*_input', 'POSITIVE_NUMERIC', array('name' => 'cpu_thermal'));
		}
		if($raw_temp == -1)
		{
			$raw_temp = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/temp*_input', 'POSITIVE_NUMERIC', array('name' => 'soc_thermal'));
		}
		if($raw_temp == -1)
		{
			// Try ACPI thermal
			$raw_temp = phodevi_linux_parser::read_sysfs_node('/sys/class/thermal/thermal_zone*/temp', 'POSITIVE_NUMERIC', array('type' => 'cpu_thermal'));
		}
		if($raw_temp == -1)
		{
			// Try ACPI thermal (Tegra works here)
			$raw_temp = phodevi_linux_parser::read_sysfs_node('/sys/class/thermal/thermal_zone*/temp', 'POSITIVE_NUMERIC', array('type' => 'CPU-therm'));
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
			// Try LM_Sensors
			$sensors = phodevi_linux_parser::read_sensors(array('CPU Temp', 'Core 0', 'Core0 Temp', 'Core1 Temp'));

			if($sensors != false && is_numeric($sensors) && $sensors > 0)
			{
				$temp_c = $sensors;
			}
		}

		if($temp_c == -1 && pts_client::executable_in_path('ipmitool'))
		{
			foreach(array('CPU Core Temp 1', 'CPU Core Temp 2',
					'CPU Core Temp 3', 'Temp 0') as $s) {

				$ipmi = phodevi_linux_parser::read_ipmitool_sensor($s);
				if($ipmi > 0 && is_numeric($ipmi))
				{
					$temp_c = $ipmi;
					return $temp_c;
				}
			}
		}
		
		return $temp_c;
	}
	

}

?>
