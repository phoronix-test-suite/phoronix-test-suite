<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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

class shell implements pts_option_interface
{
	const doc_section = 'System';
	const doc_description = 'A simple text-driven shell interface / helper to the Phoronix Test Suite. Ideal for those that may be new to the Phoronix Test Suite';

	public static function run($r)
	{
		pts_openbenchmarking::refresh_repository_lists();
		pts_client::$display->generic_heading('Interactive Shell');
		echo phodevi::system_centralized_view();
		echo PHP_EOL . (phodevi::read_property('motherboard', 'serial-number') != null ? PHP_EOL . 'System Serial Number: ' . phodevi::read_property('motherboard', 'serial-number') . PHP_EOL : null);
$x = phodevi::supported_sensors();

		// SENSORS
		$terminal_width = pts_client::terminal_width();
		$sensors = array();
		foreach(phodevi::supported_sensors(array('cpu_usage', 'cpu_temp', 'sys_temp', 'gpu_usage', 'gpu_temp', 'memory_usage')) as $sensor)
		{
			$supported_devices = call_user_func(array($sensor[2], 'get_supported_devices'));

			if($sensor == array('cpu', 'usage', 'cpu_usage'))
			{
				$supported_devices = array('summary');
			}
			else if($supported_devices === null)
			{
				$supported_devices = array(null);
			}


			foreach($supported_devices as $device)
			{
				$sensor_object = new $sensor[2](0, $device);
				$sensor_name = phodevi::sensor_object_name($sensor_object) . ':';
				$sensor_value = phodevi::read_sensor($sensor_object);
				$sensor_unit = phodevi::read_sensor_object_unit($sensor_object);
				$sensors[] = array($sensor_name, $sensor_value, $sensor_unit);
			}
		}
		$longest = array();
		foreach($sensors as $ar)
		{
			foreach($ar as $i => $item)
			{
				if(!isset($longest[$i]) || strlen($item) >= $longest[$i])
				{
					$longest[$i] = strlen($item) + 1;
				}
			}
		}
		$sensor_length = array_sum($longest);
		$sensors_per_line = floor($terminal_width / $sensor_length);

		$i = 0;
		foreach($sensors as $sensor_data)
		{
			echo str_repeat(' ', $longest[0] - strlen($sensor_data[0])) . pts_client::cli_just_bold($sensor_data[0]) . ' ' . $sensor_data[1] . str_repeat(' ', $longest[1] - strlen($sensor_data[1])) . pts_client::cli_colored_text($sensor_data[2], 'gray') . str_repeat(' ', $longest[2] - strlen($sensor_data[2]));

			$i++;
			if($i == $sensors_per_line)
			{
				$i = 0;
				echo PHP_EOL;
			}
		}

		echo PHP_EOL;
		// END OF SENSORS
		$blacklisted_commands = array('shell', 'quit', 'exit');
		do
		{
			echo 'Phoronix Test Suite command to run or ' . pts_client::cli_colored_text('help', 'green') . ' for help, ' . pts_client::cli_colored_text('commands', 'green') . ' for possible options, ' . pts_client::cli_colored_text('exit', 'green') . ' to exit: ' . PHP_EOL;
			echo PHP_EOL . pts_client::cli_colored_text((phodevi::is_root() ? '#' : '$'), 'white') . ' ' . pts_client::cli_colored_text('phoronix-test-suite', 'gray') . ' ';
			$input = pts_user_io::read_user_input();
			$argv = explode(' ', $input);
			$argc = count($argv);
			$sent_command = strtolower(str_replace('-', '_', (isset($argv[0]) ? $argv[0] : null)));
			if(!in_array($sent_command, $blacklisted_commands))
			{
				pts_client::handle_sent_command($sent_command, $argv, $argc);
				$pass_args = array();
				for($i = 1; $i < $argc; $i++)
				{
					$pass_args[] = $argv[$i];
				}

				pts_client::execute_command($sent_command, $pass_args); // Run command
			}
		}
		while($sent_command != 'exit');
	}
}

?>
