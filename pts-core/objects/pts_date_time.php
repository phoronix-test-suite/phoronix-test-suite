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

class pts_date_time
{
	public static function format_time_string($time, $input_format = "SECONDS", $standard_version = true, $round_to = 0)
	{
		switch($input_format)
		{
			case "MINUTES":
				$time_in_seconds = $time * 60;
				break;
			case "SECONDS":
			default:
				$time_in_seconds = $time;
				break;
		}

		if($round_to > 0)
		{
			$time_in_seconds += $round_to - ($time_in_seconds % $round_to);
		}

		$formatted_time = array();

		if($time_in_seconds > 0)
		{
			$time_r = array();
			$time_r[0] = array(floor($time_in_seconds / 3600), "Hour");
			$time_r[1] = array(floor(($time_in_seconds % 3600) / 60), "Minute");
			$time_r[2] = array($time_in_seconds % 60, "Second");

			foreach($time_r as $time_segment)
			{
				if($time_segment[0] > 0)
				{
					$formatted_part = $time_segment[0];

					if($standard_version)
					{
						$formatted_part .= " " . $time_segment[1];

						if($time_segment[0] > 1)
						{
							$formatted_part .= "s";
						}
					}
					else
					{
						$formatted_part .= strtolower(substr($time_segment[1], 0, 1));
					}

					array_push($formatted_time, $formatted_part);
				}
			}
		}

		return implode(($standard_version ? ", " : null), $formatted_time);
	}
}

?>
