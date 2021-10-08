<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018 - 2020, Phoronix Media
	Copyright (C) 2018 - 2020, Michael Larabel

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

class pts_result_viewer_embed
{
	protected $result_file;
	protected $can_modify_results = false;
	protected $can_delete_results = false;
	protected $result_public_id;
	protected $graph_export_handler = false;
	protected $post_description_message = null;
	protected $show_html_table_when_relevant = true;

	public function __construct(&$result_file, $public_id = null)
	{
		$this->result_file = &$result_file;
		$this->result_public_id = $public_id;
	}
	public function allow_modifying_results($can_modify)
	{
		$this->can_modify_results = $can_modify;
	}
	public function allow_deleting_results($can_delete)
	{
		$this->can_delete_results = $can_delete;
	}
	public function set_graph_export_handler($handler)
	{
		if(is_callable($handler))
		{
			$this->graph_export_handler = $handler;
		}
	}
	public function graph_export_handler(&$raw)
	{
		if($this->graph_export_handler)
		{
			return call_user_func($this->graph_export_handler, $raw);
		}
	}
	public function set_post_description_message($msg)
	{
		$this->post_description_message = $msg;
	}
	public function show_html_result_table($show)
	{
		$this->show_html_table_when_relevant = $show;
	}
	protected function result_object_to_error_report(&$result_file, &$result_object, $i)
	{
		$html = '';
		$shown_args = false;
		foreach($result_object->test_result_buffer->buffer_items as &$bi)
		{
			if($bi->get_result_value() == null)
			{
				if(!$shown_args)
				{
					$html .= '<p><strong>' . $result_object->get_arguments_description() . '</strong></p>';
					$shown_args = true;
				}
				$bi_error = $bi->get_error();
				if($bi_error == null)
				{
					$bi_error = 'Test failed to run.';
				}
				$html .= '<p class="test_error"><strong>' . $bi->get_result_identifier() . ':</strong> ' . strip_tags($bi_error) . '<br />';

				if($result_file->get_test_run_log_for_result($result_object, -2))
				{
					$html .= ' <a onclick="javascript:display_test_logs_for_result_object(\'' . $this->result_public_id . '\', \'' . $i . '\', \'' . $bi->get_result_identifier() . '\'); return false;">View Test Run Logs</a> ';
				}
				if($result_file->get_install_log_for_test($result_object->test_profile, -2))
				{
					$html .= ' &nbsp; <a onclick="javascript:display_install_logs_for_result_object(\'' . $this->result_public_id . '\', \'' . $i . '\', \'' . $bi->get_result_identifier() . '\'); return false;">View Test Installation Logs</a> ';
				}
				$html .= '</p>';
			}
		}

		return $html;
	}
	public function get_html()
	{
		$PAGE = null;
		$result_file = &$this->result_file;
		$result_file->avoid_duplicate_identifiers();
		$extra_attributes = null;
		$html_options = pts_result_viewer_settings::get_html_options_markup($result_file, $_REQUEST, $this->result_public_id, $this->can_delete_results);
		pts_result_viewer_settings::process_request_to_attributes($_REQUEST, $result_file, $extra_attributes);
		$PAGE .= pts_result_viewer_settings::get_html_sort_bar($result_file, $_REQUEST);
		$PAGE .= '<h1 id="result_file_title" placeholder="Title">' . $result_file->get_title() . '</h1>';
		$PAGE .= '<p id="result_file_desc" placeholder="Description">' . str_replace(PHP_EOL, '<br />', $result_file->get_description()) . '</p>';
		$PAGE .= '<div id="result-settings">';
		if($this->can_modify_results)
		{
			$PAGE .= ' <input type="submit" id="save_result_file_meta_button" value="Save" onclick="javascript:save_result_file_meta(\'' . $this->result_public_id . '\'); return false;" style="display: none;">';
			$PAGE .= ' <input type="submit" id="edit_result_file_meta_button" value="Edit" onclick="javascript:edit_result_file_meta(); return false;">';
		}
		if($this->can_delete_results)
		{
			$PAGE .= ' <input type="submit" value="Delete Result File" onclick="javascript:delete_result_file(\'' . $this->result_public_id . '\'); return false;">';
		}
		$PAGE .= $this->post_description_message;
		$PAGE .= '<div style="text-align: center;">Jump To <a href="#table">Table</a> - <a href="#results">Results</a></div>';
		$PAGE .= '<hr /><div style="font-size: 12pt;">' . $html_options . '</div><hr style="clear: both;" />';
		$PAGE .= pts_result_viewer_settings::process_helper_html($_REQUEST, $result_file, $extra_attributes);
		$PAGE .= '</div>';
		$PAGE .= '<div class="print_notes">' . pts_result_file_output::result_file_to_system_html($result_file) . '</div>';
		$PAGE .= '<div id="result_overview_area">';
		$intent = -1;
		if($result_file->get_system_count() == 1 || ($intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true)))
		{
			$table = new pts_ResultFileCompactSystemsTable($result_file, $intent);
		}
		else if($result_file->get_system_count() > 0)
		{
			$table = new pts_ResultFileSystemsTable($result_file);
		}

		$rendered = pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes);
		$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object" id="result_file_system_table">' . $rendered . '</p>';
		$PAGE .= $this->graph_export_handler($rendered);

		if($result_file->get_system_count() == 2)
		{
			$graph = new pts_graph_run_vs_run($result_file);

			if($graph->renderGraph())
			{
				$rendered = pts_render::render_graph_inline_embed($graph, $result_file, $extra_attributes);
				$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . $rendered . '</p>';
				$PAGE .= $this->graph_export_handler($rendered);
			}
		}
		else if($result_file->get_system_count() > 12 && false) // TODO determine when this is sane enough to enable
		{
			$graph = new pts_graph_mini_overview($result_file, '');

			if($graph->renderGraph())
			{
				$rendered = pts_render::render_graph_inline_embed($graph, $result_file, $extra_attributes);
				$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . $rendered . '</p>';
				$PAGE .= $this->graph_export_handler($rendered);
			}
		}
		else if(!$result_file->is_multi_way_comparison())
		{
			foreach(array('', 'Per Watt', 'Per Dollar') as $selector)
			{
				$graph = new pts_graph_radar_chart($result_file, $selector);

				if($graph->renderGraph())
				{
					$rendered = pts_render::render_graph_inline_embed($graph, $result_file, $extra_attributes);
					$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . $rendered . '</p>';
					$PAGE .= $this->graph_export_handler($rendered);
				}
			}
		}
		//$PAGE .= '<a id="table"></a>';
		if(!$result_file->is_multi_way_comparison() && $this->show_html_table_when_relevant)
		{
			$PAGE .= '<div class="pts_result_table">' . pts_result_file_output::result_file_to_detailed_html_table($result_file, 'grid', $extra_attributes, pts_result_viewer_settings::check_request_for_var($_REQUEST, 'sdt')) . '</div>';
		}
		else if($result_file->get_test_count() > 3)
		{
			$intent = null;
			$table = new pts_ResultFileTable($result_file, $intent);
			$rendered = pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes);
			$PAGE .= '<p style="text-align: center; overflow: auto;" class="result_object">' . $rendered . '</p>';
			$PAGE .= $this->graph_export_handler($rendered);
		}
		$PAGE .= '</div>';

		$PAGE .= '<a id="table"></a><div id="results">';
		$prev_title = null;

		$identifier_mapping_to_cores = array();
		$identifier_mapping_to_threads = array();
		$identifier_mapping_to_cpu_clock = array();
		$identifier_mapping_to_ram_channels = array();

		if($result_file->get_system_count() > 1 && !$result_file->is_multi_way_comparison())
		{
			foreach($result_file->get_systems() as $system)
			{
				$t = $system->get_cpu_core_count();
				if($t > 0)
				{
					$identifier_mapping_to_cores[$system->get_identifier()] = $t;
				}
				$t = $system->get_cpu_thread_count();
				if($t > 0)
				{
					$identifier_mapping_to_threads[$system->get_identifier()] = $t;
				}
				$t = $system->get_cpu_clock();
				if($t > 0)
				{
					$identifier_mapping_to_cpu_clock[$system->get_identifier()] = $t;
				}
				$t = $system->get_memory_channels();
				if($t > 0)
				{
					$identifier_mapping_to_ram_channels[$system->get_identifier()] = $t;
				}
			}

			if(count(array_unique($identifier_mapping_to_cores)) < 2)
			{
				$identifier_mapping_to_cores = array();
			}
			if(count(array_unique($identifier_mapping_to_threads)) < 2)
			{
				$identifier_mapping_to_threads = array();
			}
			if(count(array_unique($identifier_mapping_to_cpu_clock)) < 2)
			{
				$identifier_mapping_to_cpu_clock = array();
			}
			if(count(array_unique($identifier_mapping_to_ram_channels)) < 2)
			{
				$identifier_mapping_to_ram_channels = array();
			}
		}

		//
		// SHOW THE RESULTS
		//
		$skip_ros = array();
		foreach($result_file->get_result_objects() as $i => $result_object)
		{
			//
			// RENDER TEST AND ANCHOR
			//
			if(in_array($i, $skip_ros))
			{
				continue;
			}
			$ro = clone $result_object;
			$res_desc_shortened = $result_object->get_arguments_description_shortened(false);
			$res = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
			$PAGE .= '<a id="r-' . $i . '"></a><div style="text-align: center;" id="result-' . $i . '">';

			//
			// DISPLAY TEST PORIFLE METADATA HELPER
			//
			if($result_object->test_profile->get_title() != $prev_title)
			{
				$PAGE .= '<h2>' . $result_object->test_profile->get_title() . '</h2>';
				if(is_file(PTS_INTERNAL_OB_CACHE . 'test-profiles/' . $result_object->test_profile->get_identifier() . '/test-definition.xml'))
				{
					$tp = new pts_test_profile(PTS_INTERNAL_OB_CACHE . 'test-profiles/' . $result_object->test_profile->get_identifier() . '/test-definition.xml');
					$PAGE .= '<p class="mini">' . $tp->get_description() . ' <a href="https://openbenchmarking.org/test/' . $result_object->test_profile->get_identifier(false) . '"><em class="hide_on_print">Learn more via the OpenBenchmarking.org test page</em></a>.</p>';

				/*	$suites_containing_test = pts_test_suites::suites_containing_test_profile($result_object->test_profile);
					if(!empty($suites_containing_test))
					{
						foreach($suites_containing_test as $suite)
						{
							$PAGE .= $suite->get_title() . ' ' . $suite->get_identifier();
						}
					}  */
				}
				$prev_title = $result_object->test_profile->get_title();
			}
			if($res != false)
			{
				//
				// DISPLAY GRAPH
				//

				// Run variability
				$res_per_core = false;
				$res_per_thread = false;
				$res_per_clock = false;
				$res_per_ram = false;
				$res_variability = false;

				if(!in_array($result_object->test_profile->get_display_format(), array('LINE_GRAPH', 'BOX_PLOT')) && $result_object->test_result_buffer->detected_multi_sample_result() && $result_object->test_result_buffer->get_count() > 1)
				{
					$extra_attributes['graph_render_type'] = 'HORIZONTAL_BOX_PLOT';
					$ro = clone $result_object;
					$res_variability = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
					unset($extra_attributes['graph_render_type']);
				}
				if(in_array($result_object->test_profile->get_test_hardware_type(), array('System', 'Processor', 'OS')))
				{
					if(!empty($identifier_mapping_to_cores))
					{
						$ro = pts_result_file_analyzer::get_result_object_custom($result_file, $result_object, $identifier_mapping_to_cores, 'Performance Per Core', 'Core');
						if($ro)
						{
							$res_per_core = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
						}
					}
					if(!empty($identifier_mapping_to_threads) && $identifier_mapping_to_cores != $identifier_mapping_to_threads)
					{
						$ro = pts_result_file_analyzer::get_result_object_custom($result_file, $result_object, $identifier_mapping_to_threads, 'Performance Per Thread', 'Thread');
						if($ro)
						{
							$res_per_thread = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
						}
					}
					if(!empty($identifier_mapping_to_cpu_clock))
					{
						$ro = pts_result_file_analyzer::get_result_object_custom($result_file, $result_object, $identifier_mapping_to_cpu_clock, 'Performance Per Clock', 'GHz');
						if($ro)
						{
							$res_per_clock = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
						}
					}
				}
				if(in_array($result_object->test_profile->get_test_hardware_type(), array('System', 'Processor', 'Memory')))
				{
					if(!empty($identifier_mapping_to_ram_channels))
					{
						$ro = pts_result_file_analyzer::get_result_object_custom($result_file, $result_object, $identifier_mapping_to_ram_channels, 'Performance Per Memory Channel', 'Channel');
						if($ro)
						{
							$res_per_ram = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
						}
					}
				}

				$tabs = array(
					'Result' => $res
					);
				$show_on_print = array();

				foreach($result_file->get_relation_map($i) as $child_ro)
				{
					$c_ro = $result_file->get_result($child_ro);
					if($c_ro)
					{
						$desc = str_replace(array(' Monitor', $res_desc_shortened ,'()' ,')', ' - '), '', $c_ro->get_arguments_description_shortened(false));
						$dindex = $desc == $res_desc_shortened || empty($desc) ? $c_ro->test_profile->get_result_scale() : $desc;
						$tabs[$dindex] = pts_render::render_graph_inline_embed($c_ro, $result_file, $extra_attributes);
						$show_on_print[] = $dindex;
						$result_file->remove_result_object_by_id($child_ro);
						$skip_ros[] = $child_ro;
					}
				}

				$tabs['Perf Per Core'] = $res_per_core;
				$tabs['Perf Per Thread'] = $res_per_thread;
				$tabs['Perf Per Clock'] = $res_per_clock;
				$tabs['Perf Per RAM Channel'] = $res_per_ram;
				$tabs['Result Confidence'] = $res_variability;

				foreach($tabs as $title => &$graph)
				{
					if(empty($graph))
					{
						unset($tabs[$title]);
					}
				}
				switch(count($tabs))
				{
					case 0:
						continue 2;
					case 1:
						$PAGE .= $res . '<br />';
						$PAGE .= $this->graph_export_handler($res);
						break;
					default:
						$PAGE .= '<div class="tabs">';
						foreach($tabs as $title => &$rendered)
						{
							$tab_id = strtolower(str_replace(' ', '_', $title)) . '_' . $i;
							$PAGE .= '<input type="radio" name="tabs_' . $i . '" id="' . $tab_id . '"' . ($title == 'Result' ? ' checked="checked"' : '') . '>
							  <label for="' . $tab_id . '">' . $title . '</label>
							  <div class="tab' . (in_array($title, $show_on_print) ? ' print_notes' : '') . '">
							    ' . $rendered . $this->graph_export_handler($rendered) . '
							  </div>';
						}
						$PAGE .= '</div>';
				}
			}

			// $PAGE .= $res . '<br />';

			//
			// DISPLAY LOGS
			//
			$PAGE .= $this->result_object_to_error_report($result_file, $result_object, $i);
			$button_area = null;

			if($result_file->get_test_run_log_for_result($result_object, -2))
			{
				$button_area .= ' <button onclick="javascript:display_test_logs_for_result_object(\'' . $this->result_public_id . '\', \'' . $i . '\'); return false;">View Test Run Logs</button> ';
			}

			if($result_file->get_install_log_for_test($result_object->test_profile, -2))
			{
				$button_area .= ' <button onclick="javascript:display_install_logs_for_result_object(\'' . $this->result_public_id . '\', \'' . $i . '\'); return false;">View Test Installation Logs</button> ';
			}


			//
			// EDITING / DELETE OPTIONS
			//

			if($this->can_delete_results && !$result_object->dynamically_generated)
			{
				$button_area .= ' <button onclick="javascript:delete_result_from_result_file(\'' . $this->result_public_id . '\', \'' . $i . '\'); return false;">Delete Result</button> ';
			}
			else if($result_object->dynamically_generated)
			{
				$button_area .= ' <button onclick="javascript:hide_result_in_result_file(\'' . $this->result_public_id . '\', \'' . $i . '\'); return false;">Hide Result</button> ';
			}
			if($this->can_modify_results && !$result_object->dynamically_generated)
			{
				if($result_object->get_annotation() == null)
				{
					$button_area .= ' <button onclick="javascript:display_add_annotation_for_result_object(\'' . $this->result_public_id . '\', \'' . $i . '\', this); return false;">Add Annotation</button> ';
					$PAGE .= ' <div id="annotation_area_' . $i . '" style="display: none;"> <form action="#" onsubmit="javascript:add_annotation_for_result_object(\'' . $this->result_public_id . '\', \'' . $i . '\', this); return false;"><textarea rows="4" cols="50" placeholder="Add Annotation..." name="annotation"></textarea><br /><input type="submit" value="Add Annotation"></form></div>';
				}
				else
				{
					$PAGE .= '<div id="update_annotation_' . $i . '" contentEditable="true">' . $result_object->get_annotation() . '</div> <input type="submit" value="Update Annotation" onclick="javascript:update_annotation_for_result_object(\'' . $this->result_public_id . '\', \'' . $i . '\'); return false;">';
				}
			}
			else
			{
				$PAGE .= '<p class="mini">' . $result_object->get_annotation() . '</p>';
			}
			if($button_area != null)
			{
				$PAGE .= '<p>' . $button_area . '</p>';
			}

			$PAGE .= '</div>';
			unset($result_object);
		}

		$PAGE .= '<div class="print_notes mini" style="font-size: 10px !important;">' . pts_result_file_output::result_file_to_system_html($result_file, true) . '</div>';
		$PAGE .= '</div>';

		return $PAGE;
	}
	public static function html_template_log_viewer($html_to_show, &$result_file)
	{
		return '<!doctype html>
		<html lang="en">
		<head><title>' . ($result_file ? $result_file->get_title() . ' ' : '') . 'Log Viewer</title>
		' . (defined('CSS_RESULT_VIEWER_PATH') ? '<link rel="stylesheet" href="' . CSS_RESULT_VIEWER_PATH . '">' : '') . '</head>
		<body>' . (empty($html_to_show) ? '<p>No logs available.</p>' : $html_to_show) . '</body></html>';
	}
	public static function display_log_html_or_download(&$log_contents, &$list_of_log_files, $log_selected, &$append_to_html, $title)
	{
		$append_to_html .= '<h2 align="center">' . $title . ' Logs</h2>';
		$append_to_html .= '<div style="text-align: center;"><form action="' . str_replace('&log_select=' . $log_selected, '', str_replace('&download', '', $_SERVER['REQUEST_URI'])) . '" method="post"><select name="log_select" id="log_select" onchange="this.form.submit()">';
		foreach($list_of_log_files as $log_file)
		{
			$append_to_html .= '<option value="' . $log_file . '"' . (isset($_REQUEST['log_select']) && $log_file == $_REQUEST['log_select'] ? 'selected="selected"' : '') . '>' . $log_file . '</option>';
		}
		$append_to_html .= '</select> &nbsp; <input type="submit" value="Show Log"></form></div><br /><hr />';
		$append_to_html .= '<p style="font-size: 12px; margin: 5px; text-align: right"><form action="' . $_SERVER['REQUEST_URI'] . '" method="post"><input type="hidden" name="download" value="download" /><input type="hidden" name="log_select" value="' . $log_selected . '" /><input type="submit" value="Download Log File" style="float: right;"> </form></p>';

		if($log_contents == null)
		{
			$append_to_html .= '<p>No log file available.</p>';
		}
		else if(pts_strings::is_text_string($log_contents) && !isset($_REQUEST['download']))
		{
			$log_contents = phodevi_vfs::cleanse_file($log_contents);
			$log_contents = htmlentities($log_contents);
			$log_contents = str_replace(PHP_EOL, '<br />', $log_contents);
			$append_to_html .= '<br /><pre style="font-family: monospace;">' . $log_contents . '</pre>';
		}
		else if(isset($_REQUEST['log_select']) && $_REQUEST['log_select'] != 'undefined') // to avoid blocking the popup window in first place if it wasn't explicitly selected
		{
			if(class_exists('finfo'))
			{
				$finfo = new finfo(FILEINFO_MIME);
				header('Content-type: '. $finfo->buffer($log_contents));
			}
			//header('Content-Type: application/octet-stream');
			header('Content-Length: ' . strlen($log_contents));
			header('Content-Disposition: attachment; filename="' . str_ireplace(array('/', '\\', '.'), '', $title) . ' - ' . $log_selected . '"');
			echo $log_contents;
			exit;
		}
		else
		{
			$append_to_html .= '<p>Download log file to view.</p>';
		}
	}
}

?>
