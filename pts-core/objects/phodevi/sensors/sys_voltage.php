<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2015, Phoronix Media
	Copyright (C) 2009 - 2015, Michael Larabel

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

class sys_voltage extends phodevi_sensor
{
	const SENSOR_TYPE = 'sys';
	const SENSOR_SENSES = 'voltage';
	const SENSOR_UNIT = 'Volts';
	
	private $voltage_to_monitor = NULL;
	
	function __construct($instance, $parameter)
	{
		parent::__construct($instance, $parameter);

		if($parameter !== NULL)
		{
			$this->voltage_to_monitor = $parameter;
		}
	}
	public function support_check()
	{
		$devices = self::get_supported_devices();
		
		if(self::get_supported_devices() == null)
		{
			return false;
		}
		
		$this->voltage_to_monitor = $devices[0];
		
		$test = $this->read_sensor();
		return is_numeric($test) && $test != -1;
	}
	public static function parameter_check($parameter)
	{
		if($parameter === null || in_array($parameter, self::get_supported_devices() ) )
		{
			return true;
		}

		return false;
	}
	public function get_readable_device_name()
	{
		return strtoupper($this->voltage_to_monitor);
	}
	public static function get_supported_devices()
	{
		if(phodevi::is_linux())
		{
			$supported = array();
			
			//TODO not so elegant
			
			if(phodevi_linux_parser::read_sensors(array('V12', '+12V')) )
			{
				array_push($supported, '12v');				
			}
			if(phodevi_linux_parser::read_sensors(array('V5', '+5V') ) )
			{
				array_push($supported, '5v');				
			}
			if(phodevi_linux_parser::read_sensors(array('V3.3', '+3.3V') ) )
			{
				array_push($supported, '3v');				
			}
			
			return $supported;
		}
		return NULL;
	}
	public function read_sensor()
	{
		$sensor = -1;
		
		if(phodevi::is_linux())
		{
			if($this->voltage_to_monitor == '12v')
			{
				$sensor = phodevi_linux_parser::read_sensors(array('V12', '+12V'));
			}
			elseif($this->voltage_to_monitor == '5v')
			{
				$sensor = phodevi_linux_parser::read_sensors(array('V5', '+5V'));
			}
			elseif($this->voltage_to_monitor == '3v')
			{
				$sensor = phodevi_linux_parser::read_sensors(array('V3.3', '+3.3V'));
			}
		}
		
		return $sensor;
	}

}

?>
