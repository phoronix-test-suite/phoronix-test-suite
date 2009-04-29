<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	phodevi_monitor.php: The PTS Device Interface object for the display monitor

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

class phodevi_monitor extends pts_device_interface
{
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case "identifier":
				$property = new pts_device_property("monitor_string", true);
				break;
			default:
				$property = new pts_device_property(null, false);
				break;
		}

		return $property;
	}
	public static function monitor_string()
	{
		if(IS_MACOSX)
		{
			$system_profiler = shell_exec("system_profiler SPDisplaysDataType 2>&1");
			$system_profiler = substr($system_profiler, strrpos($system_profiler, "Displays:"));
			$system_profiler = substr($system_profiler, strpos($system_profiler, "\n"));
			$monitor = trim(substr($system_profiler, 0, strpos($system_profiler, ":")));
		}
		else
		{
			$log_parse = shell_exec("cat /var/log/Xorg.0.log 2>&1 | grep \"Monitor name\"");
			$log_parse = substr($log_parse, strpos($log_parse, "Monitor name:") + 14);
			$monitor = trim(substr($log_parse, 0, strpos($log_parse, "\n")));
		}

		return (empty($monitor) ? false : $monitor);
	}
}

?>
