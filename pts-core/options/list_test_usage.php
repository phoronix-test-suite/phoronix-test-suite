<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class list_test_usage implements pts_option_interface
{
	public static function run($r)
	{
		$installed_tests = pts_installed_tests_array();
		pts_client::$display->generic_heading(count($installed_tests) . " Tests Installed");

		if(count($installed_tests) > 0)
		{
			printf("%-18ls   %-8ls %-13ls %-11ls %-13ls %-10ls\n", "TEST", "VERSION", "INSTALL DATE", "LAST RUN", "AVG RUN-TIME", "TIMES RUN");
			foreach($installed_tests as $identifier)
			{
				$installed_test = new pts_installed_test($identifier);

				if($installed_test->get_installed_version() != null)
				{
					$avg_time = $installed_test->get_average_run_time();
					$avg_time = !empty($avg_time) ? pts_date_time::format_time_string($avg_time, "SECONDS", false) : "N/A";

					$last_run = $installed_test->get_last_run_date();
					$last_run = $last_run == "0000-00-00" ? "NEVER" : $last_run;

					printf("%-18ls - %-8ls %-13ls %-11ls %-13ls %-10ls\n", $identifier, $installed_test->get_installed_version(), $installed_test->get_install_date(), $last_run, $avg_time, $installed_test->get_run_count());
				}
			}
			echo "\n";
		}
	}
}

?>
