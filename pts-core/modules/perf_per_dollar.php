<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2020, Phoronix Media
	Copyright (C) 2015 - 2020, Michael Larabel
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
	const module_version = '1.0.0';
	const module_description = 'Setting the COST_PERF_PER_DOLLAR= environment variable to whatever value of the system cost/component you are running a comparison on will yield extra graphs that calculate the performance-per-dollar based on the test being run. The COST_PERF_PER_DOLLAR environment variable is applied just to the current test run identifier. Set the COST_PERF_PER_UNIT= environment variable if wishing to use a metric besides dollar/cost. The COST_PERF_PER_HOUR value can be used rather than COST_PERF_PER_DOLLAR if wishing to calculate the e.g. cloud time or other compute time based on an hourly basis.';
	const module_author = 'Michael Larabel';

	private static $COST_PERF_PER_DOLLAR = 0;
	private static $COST_PERF_PER_HOUR = 0;
	private static $COST_PERF_PER_UNIT = 'Dollar';
	private static $successful_test_run_request = null;
	private static $perf_per_dollar_collection;
	private static $result_identifier;
	private static $TEST_RUN_TIME_START = 0;
	private static $TEST_RUN_TIME_ELAPSED = 0;
	private static $TOTAL_TEST_RUN_TIME_PROCESS_START = 0;

	public static function module_environment_variables()
	{
		return array('COST_PERF_PER_DOLLAR', 'COST_PERF_PER_UNIT', 'COST_PERF_PER_HOUR');
	}
	public static function module_info()
	{
		return null;
	}
	public static function user_commands()
	{
		return array('add' => 'add_to_result_file');
	}
	public static function add_to_result_file($r)
	{
		if(empty($r) || !pts_types::is_result_file($r[0]))
		{
			echo 'No result file supplied.';
			return;
		}
		$result_file = new pts_result_file($r[0]);
		$result_file_identifiers = $result_file->get_system_identifiers();
		self::$result_identifier = pts_user_io::prompt_text_menu('Select the test run to use', $result_file_identifiers);
		self::$COST_PERF_PER_DOLLAR = pts_user_io::prompt_numeric_input('Enter the performance-per value');

		pts_result_file_analyzer::generate_perf_per_dollar($result_file, array(self::$result_identifier => self::$COST_PERF_PER_DOLLAR), self::$COST_PERF_PER_UNIT);
		pts_client::save_test_result($result_file->get_file_location(), $result_file->get_xml());
		pts_client::display_result_view($r[0], false);
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
		else if(($d = getenv('COST_PERF_PER_HOUR')) > 0)
		{
			self::$COST_PERF_PER_HOUR = $d;
			echo PHP_EOL . 'The Phoronix Test Suite will generate performance-per-dollar graphs with an assumed value of $' . $d . ' per hour.' . PHP_EOL;
			self::$perf_per_dollar_collection = array();
		}
		else
		{
			return pts_module::MODULE_UNLOAD; // This module doesn't have anything else to do
		}

		// This module won't be too useful if you're not saving the results to see the graphs
		$test_run_manager->force_results_save();

		if(self::$COST_PERF_PER_HOUR > 0)
		{
			// Don't want to muck up the cost estimates if needed extra run counts
			$test_run_manager->disable_dynamic_run_count();
		}
	}
	public static function __pre_run_process(&$test_run_manager)
	{
		self::$result_identifier = $test_run_manager->get_results_identifier();
		self::$TOTAL_TEST_RUN_TIME_PROCESS_START = time();
	}
	public static function __pre_test_run($test_run_request)
	{
		self::$TEST_RUN_TIME_START = time();
	}
	public static function __post_test_run($test_result)
	{
		self::$TEST_RUN_TIME_ELAPSED = time() - self::$TEST_RUN_TIME_START;
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

			if(self::$COST_PERF_PER_HOUR > 0)
			{
				// Cost-perf-per-hour calculation, e.g. cloud costs...
				// self::$TEST_RUN_TIME_ELAPSED is seconds run.....
				$cost_to_run_test = round((self::$COST_PERF_PER_HOUR / 60 / 60) * self::$TEST_RUN_TIME_ELAPSED, 2);

				if($cost_to_run_test < 0.01)
					return;

				$cost_perf_value = $cost_to_run_test;
				$footnote = '$' . self::$COST_PERF_PER_HOUR . ' reported cost per hour, test consumed ' . pts_strings::format_time(self::$TEST_RUN_TIME_ELAPSED) . ': cost approximately ' . $cost_perf_value . ' ' . strtolower(self::$COST_PERF_PER_UNIT) . '.';
			}
			else
			{
				$cost_perf_value = self::$COST_PERF_PER_DOLLAR;
				$footnote = '$' . self::$COST_PERF_PER_DOLLAR . ' reported cost.';
			}

			if(self::$successful_test_run_request->test_profile->get_result_proportion() == 'HIB')
			{
				$result = pts_math::set_precision(self::$successful_test_run_request->active->get_result() / $cost_perf_value);
				$scale = self::$successful_test_run_request->test_profile->get_result_scale() . ' Per ' . self::$COST_PERF_PER_UNIT;
			}
			else if(self::$successful_test_run_request->test_profile->get_result_proportion() == 'LIB')
			{
				$result = pts_math::set_precision(self::$successful_test_run_request->active->get_result() * $cost_perf_value);
				$scale = self::$successful_test_run_request->test_profile->get_result_scale() . ' x ' . self::$COST_PERF_PER_UNIT;
			}

			if($result != 0)
			{
				pts_result_file_analyzer::add_perf_per_graph($result_file, self::$successful_test_run_request, array(self::$result_identifier => $result), $scale, array(self::$result_identifier => $footnote));
				self::$perf_per_dollar_collection[] = self::$successful_test_run_request->active->get_result();
			}
		}
		self::$successful_test_run_request = null;
	}
	public static function __event_results_process(&$test_run_manager)
	{
		$total_elapsed_test_time = time() - self::$TOTAL_TEST_RUN_TIME_PROCESS_START;

		if(count(self::$perf_per_dollar_collection) > 2)
		{
			if(self::$COST_PERF_PER_DOLLAR > 0)
			{
				$avg = pts_math::arithmetic_mean(self::$perf_per_dollar_collection);
				$avg_perf_dollar = $avg / self::$COST_PERF_PER_DOLLAR;
				$test_profile = new pts_test_profile();
				$test_result = new pts_test_result($test_profile);
				$test_result->test_profile->set_test_title('Meta Performance Per Dollar');
				$test_result->test_profile->set_identifier(null);
				$test_result->test_profile->set_version(null);
				$test_result->test_profile->set_display_format('BAR_GRAPH');
				$test_result->test_profile->set_result_scale('Performance Per Dollar');
				$test_result->test_profile->set_result_proportion('HIB');
				$test_result->set_used_arguments_description('Performance Per Dollar');
				$test_result->set_used_arguments('Per-Per-Dollar');
				$test_result->test_result_buffer = new pts_test_result_buffer();
				$test_result->test_result_buffer->add_test_result(self::$result_identifier, pts_math::set_precision($avg_perf_dollar), null, array('install-footnote' => '$' . self::$COST_PERF_PER_DOLLAR . ' reported value. Average value: ' . pts_math::set_precision($avg) . '.'));
				$test_run_manager->result_file->add_result($test_result);
			}

			if(self::$COST_PERF_PER_HOUR > 0)
			{
				// Cost-perf-per-hour calculation, e.g. cloud costs...
				$cost_to_run_test = round((self::$COST_PERF_PER_HOUR / 60 / 60) * $total_elapsed_test_time, 2);

				if($cost_to_run_test < 0.01)
					return;
				$footnote = '$' . self::$COST_PERF_PER_HOUR . ' reported cost per hour, running tests consumed ' . pts_strings::format_time($total_elapsed_test_time) . ': cost approximately ' . $cost_to_run_test . ' ' . strtolower(self::$COST_PERF_PER_UNIT) . '.';
				$test_profile = new pts_test_profile();
				$test_result = new pts_test_result($test_profile);
				$test_result->test_profile->set_test_title('Cost To Run Tests');
				$test_result->test_profile->set_identifier(null);
				$test_result->test_profile->set_version(null);
				$test_result->test_profile->set_display_format('BAR_GRAPH');
				$test_result->test_profile->set_result_scale('Cost / Price Per Hour');
				$test_result->test_profile->set_result_proportion('LIB');
				$test_result->set_used_arguments_description('Cost / Price Per Hour');
				$test_result->set_used_arguments('Cost Price Per Hour');
				$test_result->test_result_buffer = new pts_test_result_buffer();
				$test_result->test_result_buffer->add_test_result(self::$result_identifier, pts_math::set_precision($cost_to_run_test), null, array('install-footnote' => $footnote));
				$test_run_manager->result_file->add_result($test_result);
			}
		}
	}
}
?>
