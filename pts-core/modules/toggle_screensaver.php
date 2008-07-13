<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2004-2008, Michael Larabel
	toggle_screensaver.php: A module to toggle the screensaver while tests are running on GNOME

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

class toggle_screensaver extends pts_module_interface
{
	const module_name = "Toggle Screensaver";
	const module_version = "1.0.0";
	const module_description = "This module toggles the system's screensaver while the Phoronix Test Suite is running. At this time, only the GNOME screensaver is supported.";
	const module_author = "Phoronix Media";

	static $gnome_screensaver_halted = FALSE;

	public static function __startup()
	{
		$halt_screensaver = trim(getenv("HALT_SCREENSAVER"));
		if(!empty($halt_screensaver) && !pts_string_bool($halt_screensaver))
			return;

		// GNOME Screensaver?
		$is_gnome_screensaver_enabled = trim(shell_exec("gconftool -g /apps/gnome-screensaver/idle_activation_enabled 2>&1"));

		if($is_gnome_screensaver_enabled == "true")
		{
			// Stop the GNOME Screensaver
			shell_exec("gconftool --type bool --set /apps/gnome-screensaver/idle_activation_enabled false 2>&1");
			self::$gnome_screensaver_halted = TRUE;
		}
	}
	public static function __shutdown()
	{
		if(self::$gnome_screensaver_halted == TRUE)
		{
			// Restore the GNOME Screensaver
			shell_exec("gconftool --type bool --set /apps/gnome-screensaver/idle_activation_enabled true 2>&1");
		}
	}
	public static function __pre_run_process()
	{
		// In case something didn't work as expected with the screensaver interrupt process
		shell_exec("xdg-screensaver reset 2>&1");
	}
	public static function __pre_test_run()
	{
		// In case something didn't work as expected with the screensaver interrupt process
		shell_exec("xdg-screensaver reset 2>&1");
	}
	public static function __post_run_process()
	{
		// In case something didn't work as expected with the screensaver interrupt process
		shell_exec("xdg-screensaver reset 2>&1");
	}
}

?>
