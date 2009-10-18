<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	gui_gtk_events.php: A module used in conjunction with the Phoronix Test Suite GTK2 GUI interface

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

class gui_gtk_events extends pts_module_interface
{
	const module_name = "GUI GTK Events";
	const module_version = "2.0.0";
	const module_description = "This module is used in conjunction with the Phoronix Test Suite GTK2 GUI interface. This module is automatically loaded when needed.";
	const module_author = "Phoronix Media";

	static $progress_window;

	public static function __startup()
	{
		if(!class_exists("gui_gtk"))
		{
			// The GTK interface isn't being used
			return PTS_MODULE_UNLOAD;
		}
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

	public static function __pre_run_process()
	{
		self::$progress_window = new pts_gtk_simple_progress_window("Phoronix Test Suite v" . PTS_VERSION);
	}
	public static function __pre_test_run($pts_test_result)
	{
		self::$progress_window->update_progress_bar((0 / $pts_test_result->get_attribute("TIMES_TO_RUN")) * 100, "Running " . $pts_test_result->get_attribute("TEST_TITLE"));
	}
	public static function __interim_test_run($pts_test_result)
	{
		self::$progress_window->update_progress_bar(($pts_test_result->trial_run_count() / $pts_test_result->get_attribute("TIMES_TO_RUN")) * 100, "Running " . $pts_test_result->get_attribute("TEST_TITLE"));
	}
	public static function __post_run_process()
	{
		self::$progress_window->completed();
	}
}

?>
