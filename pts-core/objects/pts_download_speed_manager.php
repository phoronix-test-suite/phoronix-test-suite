<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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

class pts_download_speed_manager
{
	private static $average_count = -1;
	private static $average_speed = -1;

	public static function update_download_speed_average($download_size, $elapsed_time)
	{
		if(self::$average_count == -1)
		{
			self::load_download_speed_averages();
		}

		$download_speed = floor($download_size / $elapsed_time); // bytes per second

		if(self::$average_count > 0 && self::$average_speed > 0)
		{
			// bytes per second
			self::$average_speed = floor(((self::$average_speed * self::$average_count) + $download_speed) / (self::$average_count + 1));
			self::$average_count++;
		}
		else
		{
			self::$average_speed = $download_speed;
			self::$average_count = 1;
		}
	}
	public static function get_average_download_speed()
	{
		if(self::$average_count == -1)
		{
			self::load_download_speed_averages();
		}

		return self::$average_speed;
	}
	public static function save_data()
	{
		self::save_download_speed_averages();
	}
	private static function load_download_speed_averages()
	{
		self::$average_count = pts_storage_object::read_from_file(PTS_CORE_STORAGE, 'download_average_count');
		self::$average_speed = pts_storage_object::read_from_file(PTS_CORE_STORAGE, 'download_average_speed');
	}
	private static function save_download_speed_averages()
	{
		pts_storage_object::set_in_file(PTS_CORE_STORAGE, 'download_average_count', self::$average_count);
		pts_storage_object::set_in_file(PTS_CORE_STORAGE, 'download_average_speed', self::$average_speed);
	}

}

?>
