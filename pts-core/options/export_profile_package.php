<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class export_profile_package implements pts_option_interface
{
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, "pts_is_test", null, "A test profile identifier must be passed.")
		);
	}
	public static function run($args)
	{
		$temp_path = pts_temp_dir();
		$to_export = $args[0];

		copy(pts_location_test($to_export), $temp_path . basename(pts_location_test($to_export)));
		mkdir($temp_path . "test-resources/");

		foreach(pts_glob(pts_location_test_resources($to_export) . "*") as $test_resource_file)
		{
			copy($test_resource_file, $temp_path . "test-resources/" . basename($test_resource_file));
		}

		$dest = pts_user_home() . $to_export . ".zip";
		shell_exec("cd " . $temp_path . "; zip -r " . $to_export . " *; mv " . $to_export . ".zip " . $dest);
		echo "\n\n" . $args . " is now exported to " . $dest . ".\n\n";

		pts_remove($temp_path);
		pts_unlink($temp_path);
	}
}

?>
