<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

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

class debug_render_test implements pts_option_interface
{
	const doc_section = 'Other';
	const doc_description = 'This option is used during the development of the Phoronix Test Suite software for testing of the result and graph rendering code-paths This option will download a large number of reference test results from LinuxBenchmarking.com.';

	public static function run($r)
	{
		$render_dir = pts_client::temporary_directory() . '/pts-render-test-310815/';
		if(!is_file($render_dir . 'mega-render-test.tar.bz2'))
		{
			pts_file_io::mkdir($render_dir);
			pts_network::download_file('http://linuxbenchmarking.com/misc/mega-render-test-310815.tar.bz2', $render_dir . 'mega-render-test.tar.bz2');
			pts_compression::archive_extract($render_dir . 'mega-render-test.tar.bz2');
		}
		define('PATH_TO_EXPORTED_PHOROMATIC_DATA', $render_dir . 'mega-render-test-310815/');

		error_reporting(E_ALL);
		//ini_set('memory_limit','2048M');

		$export_index_json = file_get_contents(PATH_TO_EXPORTED_PHOROMATIC_DATA . 'export-index.json');
		$export_index_json = json_decode($export_index_json, true);

		$start = microtime(true);
		foreach(array_keys($export_index_json['phoromatic']) as $REQUESTED)
		{
			$this_render_test = time();
			$tracker = &$export_index_json['phoromatic'][$REQUESTED];
			$triggers = $tracker['triggers'];
			echo PHP_EOL . 'STARTING RENDER TEST ON: ' . $REQUESTED . ' (' . count($triggers) . ' Triggers)' . PHP_EOL;
			$length = count($tracker['triggers']);
			$result_file = array();

			foreach($triggers as $trigger)
			{
				$results_for_trigger = glob(PATH_TO_EXPORTED_PHOROMATIC_DATA . '/' . $REQUESTED . '/' . $trigger . '/*/composite.xml');
				echo '.';
				if($results_for_trigger == false)
					continue;

				foreach($results_for_trigger as $composite_xml)
				{
					// Add to result file
					$system_name = basename(dirname($composite_xml)) . ': ' . $trigger;
					array_push($result_file, new pts_result_merge_select($composite_xml, null, $system_name));
				}
			}
			echo 'STARTING WRITER; ';
			$writer = new pts_result_file_writer(null);
			$attributes = array();
			echo 'STARTING MERGE; ';
			pts_merge::merge_test_results_process($writer, $result_file, $attributes);
			echo 'MAKING NEW RESULT FILE; ';
			$result_file = new pts_result_file($writer->get_xml());
			$extra_attributes = array('reverse_result_buffer' => true, 'force_simple_keys' => true, 'force_line_graph_compact' => true, 'force_tracking_line_graph' => true);
			//$extra_attributes['normalize_result_buffer'] = true;

			$intent = null;
			//$table = new pts_ResultFileTable($result_file, $intent);
			//echo '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';
			echo 'STARTING RESULT LOOP; ';
			foreach($result_file->get_result_objects((isset($_POST['show_only_changed_results']) ? 'ONLY_CHANGED_RESULTS' : -1)) as $i => $result_object)
			{
				if(stripos($result_object->get_arguments_description(), 'frame time') !== false)
					continue;

				echo $result_object->test_profile->get_title() . PHP_EOL;
				//echo '<h3>' . $result_object->get_arguments_description() . '</h3>';
				pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes);
				unset($result_object);
			}

			$table = new pts_ResultFileSystemsTable($result_file);
			pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes);
			echo 'RENDER TEST ON: ' . $REQUESTED . ' TOOK ' . (time() - $this_render_test) . PHP_EOL;
		}
		echo PHP_EOL . 'RENDER TEST TOOK: ' . (time() - $start) . PHP_EOL . PHP_EOL;
	}
}

?>
