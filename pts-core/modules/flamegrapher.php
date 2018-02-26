<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2016, Phoronix Media
	Copyright (C) 2016, Michael Larabel

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

class flamegrapher extends pts_module_interface
{
	const module_name = 'Generate Perf FlameGraphs For Tests';
	const module_version = '0.1.0';
	const module_description = 'Setting FLAME_GRAPH_PATH=<path to flamegraph path> will auto-load and enable this Phoronix Test Suite module. The module will generate a Linux perf FlameGraph for each test run during the benchmarking process. Details on FlameGraph @ https://github.com/brendangregg/FlameGraph';
	const module_author = 'Michael Larabel';

	private static $successful_test_run;
	private static $flame_graph_path = false;
	private static $temp_flame_dir;
	private static $save_position = 0;

	public static function module_environmental_variables()
	{
		return array('FLAME_GRAPH_PATH');
	}
	public static function module_info()
	{
		return null;
	}
	public static function __run_manager_setup(&$test_run_manager)
	{
		// Verify LINUX_PERF is set, `perf` can be found, and is Linux
		self::$flame_graph_path = getenv('FLAME_GRAPH_PATH') . '/';

		if(!is_dir(self::$flame_graph_path))
		{
			echo 'FLAME_GRAPH_PATH is not valid directory to FlameGraph.' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}
		if(!is_executable(self::$flame_graph_path . 'stackcollapse-perf.pl'))
		{
			echo 'stackcollapse-perf.pl not found.' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}
		if(!is_executable(self::$flame_graph_path . 'flamegraph.pl'))
		{
			echo 'flamegraph.pl not found.' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}
		if(!pts_client::executable_in_path('perf'))
		{
			echo 'Linux perf binary not found.' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}
		if(!pts_client::executable_in_path('perl'))
		{
			echo 'Linux perl binary not found.' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}

		echo PHP_EOL . 'Linux Perf & FlameGraph Support Enabled.' . PHP_EOL . PHP_EOL;

		// This module won't be too useful if you're not saving the results to see the graphs
		$test_run_manager->force_results_save();
	}
	public static function __pre_test_run(&$test_run_request)
	{
		// Pre-run process start perf recording
		self::$temp_flame_dir = pts_client::create_temporary_directory('flamegraph');
		$test_run_request->exec_binary_prepend='perf record -a -g -F 97 -o ' . self::$temp_flame_dir . 'perf.data ';
	}
	public static function __post_test_run_success($test_run_request)
	{
		// Base the new result object/graph off of what just ran
		// Do the copy here of it since this function is only called when test is a success
		self::$successful_test_run = clone $test_run_request;
	}
	public static function __post_test_run_process(&$result_file)
	{
		$svg_flamegraph = null;
		if(is_dir(self::$temp_flame_dir))
		{
			// Post-process perf data with FlameGraph
			shell_exec('cd ' . self::$temp_flame_dir . ' && perf script > out.stack');
			shell_exec(self::$flame_graph_path . 'stackcollapse-perf.pl ' . self::$temp_flame_dir . 'out.stack > ' . self::$temp_flame_dir . 'out.folded');
			shell_exec(self::$flame_graph_path . 'flamegraph.pl '  . self::$temp_flame_dir . 'out.folded > ' . self::$temp_flame_dir . 'out.svg');
			if(is_file(self::$temp_flame_dir . 'out.svg'))
			{
				$svg_flamegraph = file_get_contents(self::$temp_flame_dir . 'out.svg');
			}

			// Cleanup
			pts_file_io::delete(self::$temp_flame_dir, null, true);
			self::$temp_flame_dir = false;
		}

		if(self::$successful_test_run && $svg_flamegraph)
		{
			self::$save_position++;
			if(!is_dir($result_file->default_result_folder_path() . '/result-graphs'))
			{
				pts_file_io::mkdir($result_file->default_result_folder_path() . '/result-graphs', 0777, true);
			}

			// TODO XXX below is a bit hacky way of saving SVG graphs to make them appear in the local results viewer under matching test
			$s = file_put_contents($result_file->default_result_folder_path() . '/result-graphs/' . self::$save_position . '_extra1.svg', $svg_flamegraph);
			if($s)
			{
				echo PHP_EOL . 'Saved FlameGraph result to: ' . $result_file->default_result_folder_path() . '/result-graphs/' . self::$save_position . '_extra1.svg' . PHP_EOL;
			}
		}

		// Reset to be safe
		self::$successful_test_run = null;
	}
}
?>
