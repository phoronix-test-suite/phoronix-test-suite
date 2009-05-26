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

class estimate_run_time implements pts_option_interface
{
	public static function run($r)
	{
		if(empty($r[0]))
		{
			echo "\nThe name of a test, suite, or result file must be specified.\n";
		}

		echo "\n";
		$total_time = 0;
		$accurate = true;
		$run_times = pts_estimated_run_time($r, false);

		foreach($run_times as $test => $time)
		{
			echo pts_test_identifier_to_name($test) . ": ";

			if($time < 1)
			{
				$accurate = false;
				echo "N/A";
			}
			else
			{
				echo pts_format_time_string($time, "SECONDS", true, 60);
				$total_time += $time;
			}
			echo "\n";
		}

		echo "\n";

		if(count($run_times) > 1)
		{
			echo "Total Run-Time: " . (!$accurate ? "+" : "") . pts_format_time_string(pts_estimated_run_time($r, true, false), "SECONDS", true, 60) . "\n\n";
		}
	}
}

?>
