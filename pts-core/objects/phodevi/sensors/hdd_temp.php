<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2020, Phoronix Media
	Copyright (C) 2009 - 2020, Michael Larabel

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

class hdd_temp extends phodevi_sensor
{
	const SENSOR_TYPE = 'hdd';
	const SENSOR_SENSES = 'temp';
	const SENSOR_UNIT = 'Celsius';
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
		$temp = -1;

		if(phodevi::is_linux())
		{
			$temp = $this->hdd_temp_linux();
		}

		return pts_math::set_precision($temp, 2);
	}
	private function hdd_temp_linux()
	{
		$temp = -1;

		$disk_path = '/dev/' . $this->disk_to_monitor;
		$temp = phodevi_parser::read_hddtemp($disk_path);

		if($temp == -1 && strpos($this->disk_to_monitor, 'nvme') !== false && ($nvme_cli = pts_client::executable_in_path('nvme')) && is_writable($disk_path))
		{
			$nvme_cli = shell_exec($nvme_cli . ' smart-log ' . str_replace(array('n1'), '', $disk_path));
			if(($x = strpos($nvme_cli, 'temperature')) !== false)
			{
				$nvme_cli = substr($nvme_cli, $x);
				$nvme_cli = substr($nvme_cli, strpos($nvme_cli, ':') + 1);
				$nvme_cli = trim(substr($nvme_cli, 0, strpos($nvme_cli, ' C')));

				if(is_numeric($nvme_cli) && $nvme_cli > 0)
				{
					$temp = $nvme_cli;
				}
			}
		}

		if($temp == -1)
		{
			$temp = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/temp*_input', 'POSITIVE_NUMERIC', array('name' => 'nvme'));

			if($temp == null)
			{
				$temp = phodevi_linux_parser::read_sysfs_node('/sys/class/hwmon/hwmon*/temp*_input', 'POSITIVE_NUMERIC', array('name' => 'drivetemp'));
			}

			if($temp > 1000)
			{
				$temp = round($temp / 1000, 2);
			}
		}

		return $temp;
	}
}

?>
