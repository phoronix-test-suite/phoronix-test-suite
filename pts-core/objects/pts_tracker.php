<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2016, Phoronix Media
	Copyright (C) 2009 - 2016, Michael Larabel

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
	public static function list_regressions_linear(&$result_file, $threshold = 0.05, $show_only_active_regressions = true)
	{
		$regressions = array();

		foreach($result_file->get_result_objects() as $test_index => $result_object)
		{
			$prev_buffer_item = null;
			$this_test_regressions = array();

			foreach($result_object->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				if(!is_numeric($buffer_item->get_result_value()))
				{
					break;
				}

				if($prev_buffer_item != null && abs(1 - ($buffer_item->get_result_value() / $prev_buffer_item->get_result_value())) > $threshold)
				{
					if(defined('PHOROMATIC_TRACKER'))
					{
						$explode_r = explode(': ', $buffer_item->get_result_identifier());
						$explode_r_prev = explode(': ', $prev_buffer_item->get_result_identifier());

						if(count($explode_r) > 1 && $explode_r[0] != $explode_r_prev[0])
						{
							// This case wards against it looking like a regression between multiple systems on a Phoromatic Tracker
							// The premise is the format is 'SYSTEM NAME: DATE' so match up SYSTEM NAME's
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
						$this_test_regressions[] = $this_regression_marker;
					}
				}

				$prev_buffer_item = $buffer_item;
			}

			foreach($this_test_regressions as &$regression_marker)
			{
				$regressions[] = $regression_marker;
			}
		}

		return $regressions;
	}
}

?>
