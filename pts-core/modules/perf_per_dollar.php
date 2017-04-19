<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2017, Phoronix Media
	Copyright (C) 2015 - 2017, Michael Larabel
	perf_per_dollar.php: This module is derived from the system_monitor module

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

class perf_per_dollar extends pts_module_interface
{
	const module_name = 'Performance Per Dollar/Cost Calculator';
	const module_version = '0.2.0';
	const module_description = 'Setting the COST_PERF_PER_DOLLAR= environment variable to whatever value of the system cost/component you are running a comparison on will yield extra graphs that calculate the performance-per-dollar based on the test being run. The COST_PERF_PER_DOLLAR environment variable is applied just to the current test run identifier. Set the COST_PERF_PER_UNIT= environment variable if wishing to use a metric besides dollar/cost.';
	const module_author = 'Michael Larabel';

	private static $COST_PERF_PER_DOLLAR = 0;
	private static $COST_PERF_PER_UNIT = 'Dollar';
	private static $successful_test_run_request = null;
	private static $perf_per_dollar_collection;
	private static $result_identifier;

	public static function module_environmental_variables()
	{
		return array('COST_PERF_PER_DOLLAR', 'COST_PERF_PER_UNIT');
	}
	public static function module_info()
	{
		return null;
	}
	public static function __run_manager_setup(&$test_run_manager)
	{
		if(($d = getenv('COST_PERF_PER_DOLLAR')) > 0)
		{
			self::$COST_PERF_PER_DOLLAR = $d;
			echo PHP_EOL . 'The Phoronix Test Suite will generate performance-per-dollar graphs with an assumed value of $' . $d . '.' . PHP_EOL;
			self::$perf_per_dollar_collection = array();

			if(($d = getenv('COST_PERF_PER_UNIT')) != false)
			{
				self::$COST_PERF_PER_UNIT = $d;
			}
		}
		else
		{
			return pts_module::MODULE_UNLOAD; // This module doesn't have anything else to do
		}

		// This module won't be too useful if you're not saving the results to see the graphs
		$test_run_manager->force_results_save();
	}
	public static function __pre_run_process(&$test_run_manager)
	{
		self::$result_identifier = $test_run_manager->get_results_identifier();
	}
	public static function __post_test_run_success($test_run_request)
	{
		self::$successful_test_run_request = clone $test_run_request;
	}
	public static function __post_test_run_process(&$result_file)
	{
		if(self::$successful_test_run_request && self::$successful_test_run_request->test_profile->get_display_format() == 'BAR_GRAPH')
		{
			$result = 0;
			if(self::$successful_test_run_request->test_profile->get_result_proportion() == 'HIB')
			{
				$result = pts_math::set_precision(self::$successful_test_run_request->active->get_result() / self::$COST_PERF_PER_DOLLAR);
				$scale = $test_result->test_profile->get_result_scale() . ' Per ' . self::$COST_PERF_PER_UNIT;
			}
			else if(self::$successful_test_run_request->test_profile->get_result_proportion() == 'LIB')
			{
				$result = pts_math::set_precision(self::$successful_test_run_request->active->get_result() * self::$COST_PERF_PER_DOLLAR);
				$scale = $test_result->test_profile->get_result_scale() . ' x ' . self::$COST_PERF_PER_UNIT;
			}

			if($result != 0)
			{
				// This copy isn't needed but it's shorter and from port from system_monitor where there can be multiple items tracked
				$test_result = clone self::$successful_test_run_request;
				$test_result->test_profile->set_identifier(null);
				$test_result->set_used_arguments_description('Performance / Cost - ' . $test_result->get_arguments_description());
				$test_result->set_used_arguments('dollar comparison ' . $test_result->get_arguments());
				$test_result->test_profile->set_result_scale($scale);
				$test_result->test_result_buffer = new pts_test_result_buffer();
				$test_result->test_result_buffer->add_test_result(self::$result_identifier, $result, null, array('install-footnote' => '$' . self::$COST_PERF_PER_DOLLAR . ' reported cost.'));
				$result_file->add_result($test_result);
				self::$perf_per_dollar_collection[] = $test_result->active->get_result();
			}
		}
		self::$successful_test_run_request = null;
	}
	public static function __event_results_process(&$test_run_manager)
	{
		if(count(self::$perf_per_dollar_collection) > 2)
		{
			$avg = array_sum(self::$perf_per_dollar_collection) / count(self::$perf_per_dollar_collection);
			$avg_perf_dollar = $avg / self::$COST_PERF_PER_DOLLAR;
			$test_profile = new pts_test_profile();
			$test_result = new pts_test_result($test_profile);
			$test_result->test_profile->set_test_title('Meta Performance Per Dollar');
			$test_result->test_profile->set_identifier(null);
			$test_result->test_profile->set_version(null);
			$test_result->test_profile->set_result_proportion(null);
			$test_result->test_profile->set_display_format('BAR_GRAPH');
			$test_result->test_profile->set_result_scale('Performance Per Dollar');
			$test_result->test_profile->set_result_proportion('HIB');
			$test_result->set_used_arguments_description('Performance Per Dollar');
			$test_result->set_used_arguments('Per-Per-Dollar');
			$test_result->test_result_buffer = new pts_test_result_buffer();
			$test_result->test_result_buffer->add_test_result(self::$result_identifier, pts_math::set_precision($avg_perf_dollar), null, array('install-footnote' => '$' . self::$COST_PERF_PER_DOLLAR . ' reported cost. Average result: ' . pts_math::set_precision($avg) . '.'));
			$test_run_manager->result_file->add_result($test_result);
		}
	}
}
?>
