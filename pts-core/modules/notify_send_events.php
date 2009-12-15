<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	notify_send_events.php: A module used that uses notify-send to display various Phoronix Test Suite events

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

class notify_send_events extends pts_module_interface
{
	const module_name = "Notify Send Events";
	const module_version = "1.0.0";
	const module_description = "This module uses notify-send to report various Phoronix Test Suite events.";
	const module_author = "Phoronix Media";

	static $notify_send_cmd = false;

	public static function __startup()
	{
		self::$notify_send_cmd = pts_executable_in_path("notify-send");

		if(self::$notify_send_cmd == false)
		{
			// notify-send is not available, nothing to do
			return PTS_MODULE_UNLOAD;
		}
	}
	public static function notify_send_message($text_string)
	{
		shell_exec("notify-send --urgency=normal --icon=phoronix-test-suite --expire-time=60 \"" . $text_string . "\" > /dev/null 2>&1");
	}

	//
	// Installation Functions
	//

	public static function __pre_test_install($identifier)
	{
		self::notify_send_message("Installing " . $identifier);
	}

	//
	// Run Functions
	//

	public static function __pre_test_run($pts_test_result)
	{
		self::notify_send_message("Running " . $pts_test_result->get_name() . "\n   (Run 1 of " . $pts_test_result->get_times_to_run() . ")");
	}
	public static function __interim_test_run($pts_test_result)
	{
		self::notify_send_message("Running " . $pts_test_result->get_name() . "\n   (Run " . ($pts_test_result->trial_run_count() + 1) . " of " . $pts_test_result->get_times_to_run() . ")");
	}
}

?>
