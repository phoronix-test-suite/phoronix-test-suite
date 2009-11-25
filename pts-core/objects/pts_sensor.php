<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class pts_sensor
{
	private $hw_type;
	private $hw_type_string;
	private $sensor_type;
	private $read_function;
	private $sensor_unit;
	private $sensor_string;

	public function __construct($sensor_type, $hw_type, $read_function, $sensor_unit, $alternate_hw_type = null)
	{
		$this->hw_type = $hw_type;
		$this->hw_type_string = $alternate_hw_type;
		$this->sensor_type = $sensor_type;
		$this->read_function = $read_function;
		$this->sensor_unit = $sensor_unit;

		switch($sensor_type)
		{
			case "temp":
				$this->sensor_string = "Temperature";
				break;
			case "freq":
				$this->sensor_string = "Frequency";
				break;
			case "memory":
				$this->sensor_string = "Memory Usage";
				break;
			case "fan-speed":
				$this->sensor_string = "Fan Speed";
				break;
			case "disk-speed":
				$this->sensor_string = "Disk Speed";
				break;
			default:
				$this->sensor_string = ucwords($sensor_type);
				break;
		}
	}
	public function get_formatted_hardware_type()
	{
		if($this->hw_type_string != null)
		{
			$formatted = $this->hw_type_string;
		}
		else if(strlen($this->hw_type) < 4)
		{
			$formatted = strtoupper($this->hw_type);
		}
		else
		{
			$formatted = ucwords($this->hw_type);
		}

		return $formatted;
	}
	public function get_hardware_type()
	{
		return $this->hw_type;
	}
	public function get_sensor_type()
	{
		return $this->sensor_type;
	}
	public function get_read_function()
	{
		return $this->read_function;
	}
	public function get_sensor_unit()
	{
		return $this->sensor_unit;
	}
	public function get_sensor_string()
	{
		return $this->sensor_string;
	}
	public function get_identifier()
	{
		return $this->get_hardware_type() . "." . $this->get_sensor_type();
	}
	public function read_sensor()
	{
		if(is_array($this->read_function) && count($this->read_function) == 2)
		{
			$value = phodevi::read_sensor($this->read_function[0], $this->read_function[1]);
		}
		else
		{
			$value = -1;
		}

		return $value;
	}
	public function __toString()
	{
		return $this->get_identifier();
	}
}

?>
