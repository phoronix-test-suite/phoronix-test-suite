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

class hdd_write_speed implements phodevi_sensor
{
	public static function get_type()
	{
		return 'hdd';
	}
	public static function get_sensor()
	{
		return 'write-speed';
	}
	public static function get_unit()
	{
		return 'MB/s';
	}
	public static function support_check()
	{
		$test = self::read_sensor();
		return is_numeric($test) && $test != -1;
	}
	public static function read_sensor()
	{
		// speed in MB/s
		$speed = -1;

		if(phodevi::is_linux())
		{
			static $sys_disk = null;

			if($sys_disk == null)
			{
				foreach(pts_file_io::glob('/sys/class/block/sd*/stat') as $check_disk)
				{
					if(pts_file_io::file_get_contents($check_disk) != null)
					{
						$sys_disk = $check_disk;
						break;
					}
				}
			}

			$speed = phodevi_linux_parser::read_sys_disk_speed($sys_disk, 'WRITE');
		}

		return $speed;
	}
}

?>
