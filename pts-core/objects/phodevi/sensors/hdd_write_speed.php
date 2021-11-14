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

class hdd_write_speed extends phodevi_sensor
{
	const SENSOR_TYPE = 'hdd';
	const SENSOR_SENSES = 'write-speed';
	const SENSOR_UNIT = 'MB/s';
	const INSTANT_MEASUREMENT = false;

	private $disk_to_monitor = NULL;

	function __construct($instance, $parameter)
	{
		parent::__construct($instance, $parameter);

		if($parameter !== NULL)
		{
			$this->disk_to_monitor = $parameter;
		}
		else if(self::get_supported_devices() != null)
		{
			$disks = self::get_supported_devices();
			$this->disk_to_monitor = $disks[0];
		}
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
		return $this->disk_to_monitor;
	}
	public static function get_supported_devices()
	{
		if(phodevi::is_linux())
		{
			if(defined('GLOB_BRACE'))
			{
				$disk_array = pts_file_io::glob('/sys/block/{[shvm]d*,nvme*,mmcblk*}', GLOB_BRACE);
			}
			else
			{
				$disk_array = pts_file_io::glob('/sys/block/nvme*');
			}

			$supported = array();

			foreach($disk_array as $check_disk)
			{
				$stat_path = $check_disk . '/stat';
				if(is_file($stat_path) && pts_file_io::file_get_contents($stat_path) != null)
				{
					array_push($supported, basename($check_disk));
				}
			}

			return $supported;
		}
		return NULL;
	}
	public function read_sensor()
	{
		$write_speed = -1;

		if(phodevi::is_linux())
		{
			$write_speed = $this->hdd_write_speed_linux();
		}

		return pts_math::set_precision($write_speed, 2);
	}
	private function hdd_write_speed_linux()
	{
		if($this->disk_to_monitor == NULL)
		{
			return -1;
		}

		$stat_path = '/sys/class/block/' . $this->disk_to_monitor . '/stat';
		$speed = phodevi_linux_parser::read_sys_disk_speed($stat_path, 'WRITE');
		return $speed;
	}
}

?>
