<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2019, Phoronix Media
	Copyright (C) 2009 - 2019, Michael Larabel

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

class system_sensors implements pts_option_interface
{
	const doc_section = 'System';
	const doc_description = 'Display the installed system hardware and software sensors in real-time as detected by the Phoronix Test Suite Phodevi Library.';

	public static function run($r)
	{
		pts_client::$display->generic_heading('Supported Sensors For This System');
		$tabled = array();
		foreach(phodevi::query_sensors() as $sensor)
		{
			$supported_devices = call_user_func(array($sensor[2], 'get_supported_devices'));

			if($supported_devices === NULL)
			{
				$supported_devices = array(null);
			}

			foreach($supported_devices as $device)
			{
				if($sensor[0] === 'cgroup')
				{
				//	echo '- ' . phodevi::sensor_name($sensor) . PHP_EOL;
				}
				else
				{
					$sensor_object = new $sensor[2](0, $device);
					$tabled[] = array(pts_client::cli_just_italic(phodevi::sensor_object_identifier($sensor_object)) . ' ', pts_client::cli_just_bold(phodevi::sensor_object_name($sensor_object) . ': '), phodevi::read_sensor($sensor_object), pts_client::cli_colored_text(phodevi::read_sensor_object_unit($sensor_object), 'gray'));
				}
			}
		}
		echo pts_user_io::display_text_table($tabled) . PHP_EOL;
	}
}

?>
