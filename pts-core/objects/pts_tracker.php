<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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

class pts_tracker
{
	public static function generate_overview_object(&$overview_table, $overview_type)
	{
		$result_buffer = new pts_test_result_buffer();
		$days_keys = null;

		foreach($overview_table as $system_key => &$system)
		{
			if($days_keys == null)
			{
				// TODO: Rather messy and inappropriate way of getting the days keys
				$days_keys = array_keys($system);
				break;
			}
		}

		switch($overview_type)
		{
			case "GEOMETRIC_MEAN":
				$title = "Geometric Mean";
				$math_call = array("pts_math", "geometric_mean");
				break;
			case "HARMONIC_MEAN":
				$title = "Harmonic Mean";
				$math_call = array("pts_math", "harmonic_mean");
				break;
			case "AGGREGATE_SUM":
				$title = "Aggregate Sum";
				$math_call = "array_sum";
				break;
			default:
				return false;

		}

		foreach($overview_table as $system_key => &$system)
		{
			$to_show = array();

			foreach($system as &$days)
			{
				array_push($to_show, call_user_func($math_call, $days));
			}

			$result_buffer->add_test_result($system_key, implode(',', $to_show), null);
		}

		return new pts_result_file_result_object("Results Overview", null, null, "Phoromatic Tracker: " . $title, $title . " | " . implode(',', $days_keys), null, null, null, "LINE_GRAPH", $result_buffer);
	}
	public static function compact_result_file_test_object(&$mto, &$result_table = false, $identifiers_inverted = false)
	{
		// TODO: this may need to be cleaned up, its logic is rather messy
		if(count($mto->get_scale_special()) > 0)
		{
			// It's already doing something
			return;
		}

		$scale_special = array();
		$days = array();
		$systems = array();
		$prev_date = null;
		$is_tracking = true;

		if($identifiers_inverted)
		{
			$system_index = 1;
			$date_index = 0;
		}
		else
		{
			$system_index = 0;
			$date_index = 1;
		}

		foreach($mto->get_result_buffer()->get_buffer_items() as $buffer_item)
		{
			$identifier = pts_strings::trim_explode(": ", $buffer_item->get_result_identifier());

			switch(count($identifier))
			{
				case 2:
					$system = $identifier[$system_index];
					$date = $identifier[$date_index];
					break;
				case 1:
					$system = 0;
					$date = $identifier[0];
					break;
				default:
					return;
					break;
			}

			if(!isset($systems[$system]))
			{
				$systems[$system] = 0;
			}
			if(!isset($days[$date]))
			{
				$days[$date] = null;
			}

			if($is_tracking)
			{
				// Check to see if only numeric changes are being made
				$date = pts_strings::remove_from_string($date, TYPE_CHAR_NUMERIC);

				if($prev_date != null && $date != $prev_date)
				{
					$is_tracking = false;
				}

				$prev_date = $date;
			}
		}

		foreach(array_keys($days) as $day_key)
		{
			$days[$day_key] = $systems;
		}

		foreach($mto->get_result_buffer()->get_buffer_items() as $buffer_item)
		{
			$identifier = pts_strings::trim_explode(": ", $buffer_item->get_result_identifier());

			switch(count($identifier))
			{
				case 2:
					$system = $identifier[$system_index];
					$date = $identifier[$date_index];
					break;
				case 1:
					$system = 0;
					$date = $identifier[0];
					break;
				default:
					return;
					break;
			}

			$days[$date][$system] = $buffer_item->get_result_value();

			if(!is_numeric($days[$date][$system]))
			{
				return;
			}
		}

		$mto->set_scale($mto->get_scale() . ' | ' . implode(',', array_keys($days)));
		$mto->set_format((count($days) < 5 || $is_tracking == false ? "BAR_ANALYZE_GRAPH" : "LINE_GRAPH"));
		$mto->flush_result_buffer();

		$day_keys = array_keys($days);

		foreach(array_keys($systems) as $system_key)
		{
			$results = array();

			foreach($day_keys as $day_key)
			{
				array_push($results, $days[$day_key][$system_key]);
			}

			$mto->add_result_to_buffer($system_key, implode(',', $results), null);
		}

		if($result_table !== false)
		{
			foreach(array_keys($systems) as $system_key)
			{
				foreach($day_keys as $day_key)
				{
					if(!isset($result_table[$system_key][$day_key]))
					{
						$result_table[$system_key][$day_key] = array();
					}

					array_push($result_table[$system_key][$day_key], $days[$day_key][$system_key]);
				}
			}
		}
	}
	public static function list_regressions_linear(&$result_file, $threshold = 0.05, $show_only_active_regressions = true)
	{
		$regressions = array();

		foreach($result_file->get_result_objects() as $test_index => $result_object)
		{
			$prev_buffer_item = null;
			$this_test_regressions = array();

			foreach($result_object->get_result_buffer()->get_buffer_items() as $buffer_item)
			{
				if(!is_numeric($buffer_item->get_result_value()))
				{
					break;
				}

				if($prev_buffer_item != null && abs(1 - ($buffer_item->get_result_value() / $prev_buffer_item->get_result_value())) > $threshold)
				{
					if(defined("PHOROMATIC_TRACKER"))
					{
						$explode_r = explode(': ', $buffer_item->get_result_identifier());
						$explode_r_prev = explode(': ', $prev_buffer_item->get_result_identifier());

						if(count($explode_r) > 1 && $explode_r[0] != $explode_r_prev[0])
						{
							// This case wards against it looking like a regression between multiple systems on a Phoromatic Tracker
							// The premise is the format is "SYSTEM NAME: DATE" so match up SYSTEM NAME's
							continue;
						}
					}

					$this_regression_marker = new pts_test_result_regression_marker($result_object, $prev_buffer_item, $buffer_item, $test_index);

					if($show_only_active_regressions)
					{
						foreach($this_test_regressions as $index => &$regression_marker)
						{
							if(abs(1 - ($regression_marker->get_base_value() / $this_regression_marker->get_regressed_value())) < 0.04)
							{
								// 1% tolerance, regression seems to be corrected
								unset($this_test_regressions[$index]);
								$this_regression_marker = null;
								break;
							}
						}
					}

					if($this_regression_marker != null)
					{
						array_push($this_test_regressions, $this_regression_marker);
					}
				}

				$prev_buffer_item = $buffer_item;
			}

			foreach($this_test_regressions as &$regression_marker)
			{
				array_push($regressions, $regression_marker);
			}
		}

		return $regressions;
	}
}

?>
