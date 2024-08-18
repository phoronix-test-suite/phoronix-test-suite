<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2019, Phoronix Media
	Copyright (C) 2013 - 2019, Michael Larabel

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

class debug_self_test implements pts_option_interface
{
	const doc_section = 'Other';
	const doc_description = 'This option is used during the development of the Phoronix Test Suite software for testing of internal interfaces, commands, and other common code paths. The produced numbers should only be comparable for the same version of the Phoronix Test Suite, on the same hardware/software system, conducted on the same day of testing. This isn\'t intended as any scientific benchmark but simply to stress common PHP code paths and looking for hot areas to optimize, etc.';

	public static function run($r)
	{
		define('PHOROMATIC_PROCESS', true);
		$commands = array(
			'system_info' => null,
			'list_available_tests' => null,
			'list_available_suites' => null,
			'info' => array('pts/all'),
			'clone_openbenchmarking_result' => array('1107247-LI-MESACOMMI48', '1509040-HA-GCCINTELS17', '1508201-HA-GTX95073337', '1508233-HA-INTELSKYL16'),
			'refresh_graphs' => array('1107247-LI-MESACOMMI48', '1711094-AL-ZOTACGEFO61', '1711073-AL-GTX770TIL45', '1710268-AL-CPUTESTS119'),
			'result_file_to_text' => array('1107247-LI-MESACOMMI48'),
			'merge_results' => array('1107247-LI-MESACOMMI48', '1509040-HA-GCCINTELS17', '1508201-HA-GTX95073337', '1508233-HA-INTELSKYL16', '1711094-AL-ZOTACGEFO61', '1711073-AL-GTX770TIL45', '1710268-AL-CPUTESTS119'),
			'diagnostics' => null,
			'dump_possible_options' => null,
			'debug_render_test' => null,
			);

		$individual_times = array();

		phodevi::clear_cache();

		$start = microtime(true);
		foreach($commands as $command => $args)
		{
			echo PHP_EOL . '### ' . $command . ' ###' . PHP_EOL;
			$individual_times[$command] = array();

			for($i = 0; $i < 3; $i++)
			{
				$c_start = microtime(true);
				pts_client::execute_command($command, $args);
				$c_finish = microtime(true);
				$individual_times[$command][] = ($c_finish - $c_start);
			}
		}
		$finish = microtime(true);

		echo PHP_EOL . PHP_EOL . '### OVERALL DATA ###' . PHP_EOL . PHP_EOL;
		echo 'PHP:  ' . PTS_PHP_VERSION . PHP_EOL;

		$longest_c = max(array_map('strlen', array_keys($individual_times)));
		foreach($individual_times as $component => $times)
		{
			echo strtoupper($component) . ': ' . (str_repeat(' ', $longest_c - strlen($component))) . pts_math::set_precision(round(pts_math::arithmetic_mean($times), 3), 3) . ' seconds' . PHP_EOL;
		}

		echo PHP_EOL . 'TOTAL ELAPSED TIME: ' . (str_repeat(' ', $longest_c - strlen('ELAPSED TIME'))) . round($finish - $start, 3) . ' seconds';
		echo PHP_EOL . 'PEAK MEMORY USAGE: ' . (str_repeat(' ', $longest_c - strlen('PEAK MEMORY USAGE'))) . round(memory_get_peak_usage(true) / 1048576, 3) . ' MB';
		echo PHP_EOL . 'PEAK MEMORY USAGE EMALLOC: ' . (str_repeat(' ', $longest_c - strlen('PEAK MEMORY USAGE (emalloc)'))) . round(memory_get_peak_usage() / 1048576, 3) . ' MB';
		echo PHP_EOL;
	}
}

?>
