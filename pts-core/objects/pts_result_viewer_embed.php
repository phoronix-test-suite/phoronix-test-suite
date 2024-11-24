<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018 - 2024, Phoronix Media
	Copyright (C) 2018 - 2024, Michael Larabel

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
	protected $show_test_metadata_helper = true;
	protected $include_page_print_only_helpers = true;
	protected $show_result_sidebar = false;
	protected $print_html_immediately = false;

	public function __construct(&$result_file, $public_id = null)
	{
		$this->result_file = &$result_file;
		$this->result_public_id = $public_id;
		$this->show_result_sidebar = !defined('PHOROMATIC_SERVER_WEB_INTERFACE');
		$this->print_html_immediately = (defined('OPENBENCHMARKING_BUILD') || (defined('RESULT_VIEWER_VERSION') && RESULT_VIEWER_VERSION > 2)) && !isset($_REQUEST['export']);

		if(isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI']))
		{
			pts_strings::exit_if_contains_unsafe_data($_SERVER['REQUEST_URI']);
		}
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
	public function graph_export_handler(&$raw, &$result_file = null, &$result_object = null)
	{
		if($this->graph_export_handler)
		{
			return call_user_func($this->graph_export_handler, $raw, $result_file, $result_object);
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
	public function show_test_metadata_helper($show)
	{
		$this->show_test_metadata_helper = $show;
	}
	public function include_page_print_only_helpers($show)
	{
		$this->include_page_print_only_helpers = $show;
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
	public function print_handler(&$html, $str)
	{
		if($this->print_html_immediately)
		{
			echo $str;
		}
		else
		{
			$html .= $str;
		}
	}
	public function get_html()
	{
		$PAGE = null;
		$result_file = &$this->result_file;
		self::process_result_modify_pre_render($result_file, $this->can_modify_results, $this->can_delete_results);
		$result_file->avoid_duplicate_identifiers();
		$extra_attributes = null;
		$html_options = self::get_html_options_markup($result_file, $_REQUEST, $this->result_public_id, $this->can_delete_results);
		self::process_request_to_attributes($_REQUEST, $result_file, $extra_attributes);
		$this->print_handler($PAGE, self::get_html_sort_bar($result_file, $_REQUEST));
		$this->print_handler($PAGE, '<h1 id="result_file_title" placeholder="Title">' . pts_strings::sanitize($result_file->get_title()) . '</h1>');
		$this->print_handler($PAGE, '<p id="result_file_desc" placeholder="Description">' . str_replace(PHP_EOL, '<br />', pts_strings::sanitize($result_file->get_description())) . '</p>');
		$this->print_handler($PAGE, '<div id="result-settings">');
		if($this->can_modify_results)
		{
			$this->print_handler($PAGE, ' <input type="submit" id="save_result_file_meta_button" value="Save" onclick="javascript:save_result_file_meta(\'' . $this->result_public_id . '\'); return false;" style="display: none;">');
			$this->print_handler($PAGE, ' <input type="submit" id="edit_result_file_meta_button" value="Edit" onclick="javascript:edit_result_file_meta(); return false;">');
		}
		if($this->can_delete_results && !defined('PHOROMATIC_SERVER'))
		{
			$this->print_handler($PAGE, ' <input type="submit" value="Delete Result File" onclick="javascript:delete_result_file(\'' . $this->result_public_id . '\'); return false;">');
		}
		$this->print_handler($PAGE, $this->post_description_message);
		$this->print_handler($PAGE, '<div style="text-align: center;">Jump To <a href="#table">Table</a> - <a href="#results">Results</a></div>');
		$this->print_handler($PAGE, '<hr /><div style="font-size: 12pt;">' . $html_options . '</div><hr style="clear: both;" />');
		$this->print_handler($PAGE, self::process_helper_html($_REQUEST, $result_file, $extra_attributes, $this->can_modify_results, $this->can_delete_results));
		$this->print_handler($PAGE, '</div>');
		if($this->include_page_print_only_helpers)
		{
			$this->print_handler($PAGE, '<div class="print_notes">' . pts_result_file_output::result_file_to_system_html($result_file) . '</div>');
		}
		$this->print_handler($PAGE, '<div id="result_overview_area">');
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
		$this->print_handler($PAGE, '<p style="text-align: center; overflow: auto;" class="result_object" id="result_file_system_table">' . $rendered . '</p>');
		$this->print_handler($PAGE, $this->graph_export_handler($rendered, $result_file));

		if($result_file->get_system_count() == 2)
		{
			$graph = new pts_graph_run_vs_run($result_file);

			if($graph->renderGraph())
			{
				$rendered = pts_render::render_graph_inline_embed($graph, $result_file, $extra_attributes);
				$this->print_handler($PAGE, '<p style="text-align: center; overflow: auto;" class="result_object">' . $rendered . '</p>');
				$this->print_handler($PAGE, $this->graph_export_handler($rendered, $result_file));
			}
		}
		else if($result_file->get_system_count() > 12 && false) // TODO determine when this is sane enough to enable
		{
			$graph = new pts_graph_mini_overview($result_file, '');

			if($graph->renderGraph())
			{
				$rendered = pts_render::render_graph_inline_embed($graph, $result_file, $extra_attributes);
				$this->print_handler($PAGE, '<p style="text-align: center; overflow: auto;" class="result_object">' . $rendered . '</p>');
				$this->print_handler($PAGE, $this->graph_export_handler($rendered, $result_file));
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
					$this->print_handler($PAGE, '<p style="text-align: center; overflow: auto;" class="result_object">' . $rendered . '</p>');
					$this->print_handler($PAGE, $this->graph_export_handler($rendered, $result_file));
				}
			}
		}
		//$this->print_handler($PAGE, '<a id="table"></a>');
		if(!$result_file->is_multi_way_comparison() && $this->show_html_table_when_relevant)
		{
			$this->print_handler($PAGE, '<div class="pts_result_table">' . pts_result_file_output::result_file_to_detailed_html_table($result_file, 'grid', $extra_attributes, self::check_request_for_var($_REQUEST, 'sdt')) . '</div>');
		}
		else if($result_file->get_test_count() > 3)
		{
			$intent = null;
			$table = new pts_ResultFileTable($result_file, $intent);
			$rendered = pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes);
			$this->print_handler($PAGE, '<p style="text-align: center; overflow: auto;" class="result_object">' . $rendered . '</p>');
			$this->print_handler($PAGE, $this->graph_export_handler($rendered, $result_file));
		}
		$this->print_handler($PAGE, '</div>');
		$this->print_handler($PAGE, '<a id="table"></a><div id="results">');
		$prev_title = null;

		$identifier_mapping_to_cores = array();
		$identifier_mapping_to_threads = array();
		$identifier_mapping_to_cpu_clock = array();
		$identifier_mapping_to_ram_channels = array();

		if($result_file->get_system_count() > 1 && !$result_file->is_multi_way_comparison())
		{
			$sppt = self::check_request_for_var($_REQUEST, 'sppt');
			$sppc = self::check_request_for_var($_REQUEST, 'sppc');
			$sppm = self::check_request_for_var($_REQUEST, 'sppm');
			if($sppt || $sppc || $sppm)
			{
				foreach($result_file->get_systems() as $system)
				{
					if($sppt)
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
					}
					if($sppc)
					{
						$t = $system->get_cpu_clock();
						if($t > 0)
						{
							$identifier_mapping_to_cpu_clock[$system->get_identifier()] = $t;
						}
					}
					if($sppm)
					{
						$t = $system->get_memory_channels();
						if($t > 0)
						{
							$identifier_mapping_to_ram_channels[$system->get_identifier()] = $t;
						}
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
		}

		//
		// SHOW THE RESULTS
		//
		$sidebar_list = array();
		$skip_ros = array();
		foreach($result_file->get_result_object_keys() as $i)
		{
			$result_object = $result_file->get_result_object_by_hash($i);
			//
			// RENDER TEST AND ANCHOR
			//
			if(in_array($i, $skip_ros) || $result_object == false)
			{
				continue;
			}
			$ro = clone $result_object;
			$res_desc_shortened = $result_object->get_arguments_description_shortened(false);
			$res = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
			$this->print_handler($PAGE, '<section id="r-' . $i . '" style="text-align: center;">');

			//
			// DISPLAY TEST PORIFLE METADATA HELPER
			//
			if($this->show_test_metadata_helper && $result_object->test_profile->get_title() != $prev_title)
			{
				$this->print_handler($PAGE, '<h2>' . $result_object->test_profile->get_title() . '</h2>');
				if(is_file(PTS_INTERNAL_OB_CACHE . 'test-profiles/' . $result_object->test_profile->get_identifier() . '/test-definition.xml'))
				{
					$tp = new pts_test_profile(PTS_INTERNAL_OB_CACHE . 'test-profiles/' . $result_object->test_profile->get_identifier() . '/test-definition.xml');
					$this->print_handler($PAGE, '<p class="mini">' . $tp->get_description() . ' <a href="https://openbenchmarking.org/test/' . $result_object->test_profile->get_identifier(false) . '"><em class="hide_on_print">Learn more via the OpenBenchmarking.org test page.</em></a></p>');

				/*	$suites_containing_test = pts_test_suites::suites_containing_test_profile($result_object->test_profile);
					if(!empty($suites_containing_test))
					{
						foreach($suites_containing_test as $suite)
						{
							$this->print_handler($PAGE, $suite->get_title() . ' ' . $suite->get_identifier());
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

				if(self::check_request_for_var($_REQUEST, 'src') && !in_array($result_object->test_profile->get_display_format(), array('LINE_GRAPH', 'BOX_PLOT')) && $result_object->test_result_buffer->detected_multi_sample_result() && $result_object->test_result_buffer->get_count() > 1)
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
				if(!empty($identifier_mapping_to_ram_channels) && in_array($result_object->test_profile->get_test_hardware_type(), array('System', 'Processor', 'Memory')))
				{
					$ro = pts_result_file_analyzer::get_result_object_custom($result_file, $result_object, $identifier_mapping_to_ram_channels, 'Performance Per Memory Channel', 'Channel');
					if($ro)
					{
						$res_per_ram = pts_render::render_graph_inline_embed($ro, $result_file, $extra_attributes);
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
						$graph = pts_render::render_graph_process($c_ro, $result_file, false, $extra_attributes);
						if($c_ro->test_profile->get_result_scale() == 'Watts' && $graph)
						{
							$run_counts_for_identifier = array();
							foreach($result_object->test_result_buffer->buffer_items as $bi)
							{
								if($bi->get_sample_count() > 0 && $bi->get_result_value() != '')
								{
									$run_counts_for_identifier[$bi->get_result_identifier()] = $bi->get_sample_count();
								}
							}
							/*
							foreach($c_ro->test_result_buffer->buffer_items as $bi)
							{
								$res_tally = $bi->get_result_value();
								if(!is_array($res_tally))
								{
									$res_tally = explode(',', $res_tally);
								}
								if(is_array($res_tally) && !empty($res_tally) && isset($run_counts_for_identifier[$bi->get_result_identifier()]))
								{
									$res_tally = array_sum($res_tally);
									$graph->addTestNote($bi->get_result_identifier() . ': Approximate power consumption of ' . round($res_tally / $run_counts_for_identifier[$bi->get_result_identifier()]) . ' Joules per run.');
								}
							}*/
						}
						$tabs[$dindex] = pts_render::render_graph_inline_embed($graph, $result_file, $extra_attributes);
						$show_on_print[] = $dindex;
						$result_file->remove_result_object_by_id($child_ro, false);
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
						$this->print_handler($PAGE, $res . '<br />' . $this->graph_export_handler($res, $result_file, $ro));
						break;
					default:
						$this->print_handler($PAGE, '<div class="tabs">');
						foreach($tabs as $title => &$rendered)
						{
							$tab_id = strtolower(str_replace(' ', '_', $title)) . '_' . $i;
							$this->print_handler($PAGE, '<input type="radio" name="tabs_' . $i . '" id="' . $tab_id . '"' . ($title == 'Result' ? ' checked="checked"' : '') . '>
							  <label for="' . $tab_id . '">' . $title . '</label>
							  <div class="tab' . (in_array($title, $show_on_print) ? ' print_notes' : '') . '">
							    ' . $rendered . $this->graph_export_handler($rendered, $result_file, $ro) . '
							  </div>');
						}
						$this->print_handler($PAGE, '</div>');
				}
				if($this->show_result_sidebar)
				{
					$sidebar_list[] = &$result_object;
				}
			}

			//
			// DISPLAY LOGS
			//
			$this->print_handler($PAGE, $this->result_object_to_error_report($result_file, $result_object, $i));
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
					$this->print_handler($PAGE, ' <div id="annotation_area_' . $i . '" style="display: none;"> <form action="#" onsubmit="javascript:add_annotation_for_result_object(\'' . $this->result_public_id . '\', \'' . $i . '\', this); return false;"><textarea rows="4" cols="50" placeholder="Add Annotation..." name="annotation"></textarea><br /><input type="submit" value="Add Annotation"></form></div>');
				}
				else
				{
					$this->print_handler($PAGE, '<div id="update_annotation_' . $i . '" contentEditable="true">' . pts_strings::sanitize($result_object->get_annotation()) . '</div> <input type="submit" value="Update Annotation" onclick="javascript:update_annotation_for_result_object(\'' . $this->result_public_id . '\', \'' . $i . '\'); return false;">');
				}
			}
			else
			{
				$this->print_handler($PAGE, '<p class="mini">' . pts_strings::sanitize($result_object->get_annotation()) . '</p>');
			}
			if($button_area != null)
			{
				$this->print_handler($PAGE, '<p>' . $button_area . '</p>');
			}

			$this->print_handler($PAGE, '</section>');
			unset($result_object);
		}

		if($this->show_result_sidebar && count($sidebar_list) > 6)
		{
			// show result sidebar
			$this->print_handler($PAGE, $this->add_result_sidebar($sidebar_list));
		}

		if($this->include_page_print_only_helpers)
		{
			$this->print_handler($PAGE, '<div class="print_notes mini" style="font-size: 10px !important;">' . pts_result_file_output::result_file_to_system_html($result_file, true) . '</div>');
		}
		$this->print_handler($PAGE, '</div>');

		return $PAGE;
	}
	public function add_result_sidebar(&$sidebar_list)
	{
		$sidebar_count = count($sidebar_list);
		$html = '<div id="results_sidebar">
		<h3>' . $sidebar_count . ' Results Shown</h3>';
		$current_test = false;
		$show_units = false;
		foreach($sidebar_list as $sidebar_pos => &$result_object)
		{
			if(($sidebar_pos + 1) < $sidebar_count && $sidebar_list[($sidebar_pos + 1)]->test_profile->get_identifier() == $result_object->test_profile->get_identifier())
			{
				if($sidebar_pos == 0 || $sidebar_list[($sidebar_pos - 1)]->test_profile->get_identifier() != $result_object->test_profile->get_identifier())
				{
					// Make it nested for first occurence
					$html .= $result_object->test_profile->get_title() . ':<br />';
					$show_units = $sidebar_list[($sidebar_pos + 1)]->get_arguments_description_shortened(false) == $result_object->get_arguments_description_shortened(false) ? $result_object->get_arguments_description_shortened(false) : false;
					$current_test = &$result_object->test_profile;
				}
			}
			if($current_test && $current_test->get_identifier() == $result_object->test_profile->get_identifier())
			{
				// show nested multi conf
				if($show_units)
				{
					if(!isset($current_unit) || $current_unit != $result_object->get_arguments_description_shortened(false))
					{
						$current_unit = $result_object->get_arguments_description_shortened(false);
						$html .= ' &nbsp; <strong>' . $current_unit . ':</strong><br />';
					}
				$html .= ' &nbsp; &nbsp; <a href="#r-' . $result_object->get_comparison_hash(true, false) . '">' . $result_object->test_profile->get_result_scale() . '</a><br />';
				}
				else
				{
					$html .= ' &nbsp; <a href="#r-' . $result_object->get_comparison_hash(true, false) . '">' . $result_object->get_arguments_description_shortened(false) . '</a><br />';
				}
			}
			else
			{
				$html .= '<a href="#r-' . $result_object->get_comparison_hash(true, false) . '">' . $result_object->test_profile->get_title() . '</a><br />';
			}
			$current_test = &$result_object->test_profile;
		}
		$html .= '</div>
		<script type="text/javascript">
		var sections = document.querySelectorAll("section[id]");
		window.addEventListener("scroll", highlight_results_sidebar);
		function highlight_results_sidebar() {
		  var did_highlight = false;
		  let scroll_y = window.pageYOffset;
		  sections.forEach(current => {
		  const section_top = current.offsetTop - 50;
		  sectionId = current.getAttribute("id");
		  if(scroll_y >= section_top && scroll_y <= section_top + current.offsetHeight && document.querySelector("#results_sidebar a[href*=" + sectionId + "]"))
		  {
		    document.querySelector("#results_sidebar a[href*=" + sectionId + "]").classList.add("active");
		    document.querySelector("#results_sidebar a[href*=" + sectionId + "]").scrollIntoView({behavior: "smooth", block: "center", inline: "center"});
		    did_highlight = true;
		  }
		  else if(document.querySelector("#results_sidebar a[href*=" + sectionId + "]"))
		  {
		    document.querySelector("#results_sidebar a[href*=" + sectionId + "]").classList.remove("active");
		  }
		});
		if(did_highlight)
		{
			document.getElementById("results_sidebar").style.display = "block";
			document.getElementById("results").style.marginLeft = "300px";
		}
		else
		{
			document.getElementById("results_sidebar").style.display = "none";
		}
		}
		</script>';
		return $html;
	}
	public static function html_template_log_viewer($html_to_show, &$result_file)
	{
		return '<!doctype html>
		<html lang="en">
		<head><title>' . ($result_file ? $result_file->get_title() . ' ' : '') . 'Log Viewer</title>
		' . (defined('CSS_RESULT_VIEWER_PATH') ? '<link rel="stylesheet" href="' . CSS_RESULT_VIEWER_PATH . '">' : '') . '</head>
		<body>' . (empty($html_to_show) ? '<p>No logs available.</p>' : $html_to_show) . '</body></html>';
	}
	public static function display_log_html_or_download(&$log_contents, &$list_of_log_files, $log_selected, &$append_to_html, $title, $identifiers_with_logs = false)
	{
		$append_to_html .= '<h2 align="center">' . $title . ' Logs</h2>';
		if(empty($list_of_log_files) && $identifiers_with_logs && !empty($identifiers_with_logs))
		{
			$append_to_html = '[DEBUG] No log files were found for this system identifier (' . $title . '), but logs were found for: ' . implode(', ', $identifiers_with_logs);
		}
		$append_to_html .= '<div style="text-align: center;"><form action="' . str_replace('&log_select=' . $log_selected, '', str_replace('&download', '', $_SERVER['REQUEST_URI'])) . '" method="post">';
		$append_to_html .= '<input type="hidden" name="modify" value="0" /><select name="log_select" id="log_select" onchange="this.form.submit()">';
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
	public static function get_html_sort_bar(&$result_file, &$request)
	{
		$analyze_options = null;
		$drop_down_menus = array('Export Benchmark Data' => array(
						'export=pdf' => 'Result File To PDF',
						'export=txt' => 'Result File To Text',
						'export=html' => 'Result File To HTML',
						'export=json' => 'Result File To JSON',
					//	'export=xml' => 'Result File To XML',
						'export=xml-suite' => 'Result File To Test Suite (XML)',
						'export=csv' => 'Result File To CSV/Excel',
						'export=csv-all' => 'Individual Run Data To CSV/Excel',
						),
					);
		if(count($result_file->get_system_identifiers()) > 1)
		{
			$drop_down_menus['Sort Result Order'] = array(
				'sro&rro' => 'By Identifier (DESC)',
				'sro' => 'By Identifier (ASC)',
				'sor' => 'By Performance (DESC)',
				'sor&rro' => 'By Performance (ASC)',
				'rdt&rro' => 'By Run Date/Time (DESC)',
				'rdt' => 'By Run Date/Time (ASC)',
				);
		}
		if($result_file->get_test_count() > 1)
		{
			$drop_down_menus['Sort Graph Order'] = array(
				'grs' => 'By Result Spread',
				'grw' => 'By Common Workloads',
				'gru' => 'By Result Unit',
				'grt' => 'By Test Title',
				'grr' => 'By Test Length/Time'
				);
		}

		$analyze_options .= '<div style="float: right;"><ul>';
		foreach(array_reverse($drop_down_menus, true) as $menu => $sub_menu)
		{
			$analyze_options .= '<li><a href="#">' . $menu . '</a><ul>';
			foreach($sub_menu as $option => $txt)
			{
				$uri = $_SERVER['REQUEST_URI'];
				foreach(array_reverse(array_keys($sub_menu)) as $rem)
				{
					$uri = str_replace('&' . $rem, '', $uri);
				}
				$uri = str_replace('&rro', '', $uri);
				$analyze_options .= '<li><a href="' . $uri . '&' . $option . '">' . $txt . '</a></li>';
			}
			$analyze_options .= '</ul></li>';
		}
		$analyze_options .= '</ul></div>';
		return $analyze_options;
	}
	public static function get_html_options_markup(&$result_file, &$request, $public_id = null, $can_delete_results = false)
	{
		if($public_id == null && defined('RESULTS_VIEWING_ID'))
		{
			$public_id = RESULTS_VIEWING_ID;
		}
		$analyze_options = null;

		// CHECKS FOR DETERMINING OPTIONS TO DISPLAY
		$has_identifier_with_color_brand = false;
		$has_box_plot = false;
		$has_line_graph = false;
		$is_multi_way = $result_file->is_multi_way_comparison();
		$system_count = $result_file->get_system_count();

		foreach($result_file->get_system_identifiers() as $sys)
		{
			if(pts_render::identifier_to_brand_color($sys, null) != null)
			{
				$has_identifier_with_color_brand = true;
				break;
			}
		}

		$multi_test_run_options_tracking = array();
		$tests_with_multiple_versions = array();
		$has_test_with_multiple_options = false;
		$has_test_with_multiple_versions = false;
		foreach($result_file->get_result_object_keys() as $ro_key)
		{
			$result_object = $result_file->get_result_object_by_hash($ro_key);
			if($result_object == false)
			{
				continue;
			}
			if(!$has_box_plot && $result_object->test_profile->get_display_format() == 'HORIZONTAL_BOX_PLOT')
			{
				$has_box_plot = true;
			}
			if(!$has_line_graph && $result_object->test_profile->get_display_format() == 'LINE_GRAPH')
			{
				$has_line_graph = true;
			}
			if(!$is_multi_way && !$has_test_with_multiple_options)
			{
				if(!isset($multi_test_run_options_tracking[$result_object->test_profile->get_identifier()]))
				{
					$multi_test_run_options_tracking[$result_object->test_profile->get_identifier()] = array();
				}
				$multi_test_run_options_tracking[$result_object->test_profile->get_identifier()][] = $result_object->get_arguments_description();
				if(count($multi_test_run_options_tracking[$result_object->test_profile->get_identifier()]) > 1)
				{
					$has_test_with_multiple_options = true;
					unset($multi_test_run_options_tracking);
				}
			}
			if(!$is_multi_way && !$has_test_with_multiple_versions)
			{
				$ti_no_version = $result_object->test_profile->get_identifier(false);
				if(!isset($tests_with_multiple_versions[$ti_no_version]))
				{
					$tests_with_multiple_versions[$ti_no_version] = array();
				}
				pts_arrays::unique_push($tests_with_multiple_versions[$ti_no_version], $result_object->test_profile->get_app_version());
				if(count($tests_with_multiple_versions[$ti_no_version]) > 1)
				{
					$has_test_with_multiple_versions = true;
					unset($tests_with_multiple_versions);
				}
			}

			// (optimization) if it has everything, break
			if($has_line_graph && $has_box_plot && $has_test_with_multiple_options && $has_test_with_multiple_versions)
			{
				break;
			}
		}
		$suites_in_result_file = $system_count > 1 && self::check_request_for_var($request, 'lcs') ? pts_test_suites::suites_in_result_file($result_file, true, 0) : array();
		// END OF CHECKS

		$analyze_options .= '<form action="' . $_SERVER['REQUEST_URI'] . '" method="post">';
		$analyze_checkboxes = array(
			'View' => array(),
			'Statistics' => array(),
			'Sorting' => array(),
			'Graph Settings' => array(),
			'Additional Graphs' => array(),
			'Multi-Way Comparison' => array(),
			);

		if($system_count > 1)
		{
			$analyze_checkboxes['Statistics'][] = array('shm', 'Show Overall Harmonic Mean(s)');
			$analyze_checkboxes['Statistics'][] = array('sgm', 'Show Overall Geometric Mean');
			if(count($suites_in_result_file) > 1)
			{
				$analyze_checkboxes['Statistics'][] = array('sts', 'Show Geometric Means Per-Suite/Category');
			}
			$analyze_checkboxes['Statistics'][] = array('swl', 'Show Wins / Losses Counts (Pie Chart)');
			$analyze_checkboxes['Statistics'][] = array('nor', 'Normalize Results');
			$analyze_checkboxes['Graph Settings'][] = array('ftr', 'Force Line Graphs Where Applicable');
			$analyze_checkboxes['Graph Settings'][] = array('scalar', 'Convert To Scalar Where Applicable');
			$analyze_checkboxes['View'][] = array('hnr', 'Do Not Show Noisy Results');
			$analyze_checkboxes['View'][] = array('hni', 'Do Not Show Results With Incomplete Data');
			$analyze_checkboxes['View'][] = array('hlc', 'Do Not Show Results With Little Change/Spread');
			$analyze_checkboxes['View'][] = array('spr', 'List Notable Results');
			$analyze_checkboxes['View'][] = array('src', 'Show Result Confidence Charts');
			$analyze_checkboxes['View'][] = array('lcs', 'Allow Limiting Results To Certain Suite(s)');

			if($has_identifier_with_color_brand)
			{
				$analyze_checkboxes['Graph Settings'][] = array('ncb', 'Disable Color Branding');
			}

			// Additional Graphs
			if(!$result_file->is_multi_way_comparison())
			{
				$identifier_mapping_to_cores = array();
				$identifier_mapping_to_threads = array();
				$identifier_mapping_to_cpu_clock = array();
				$identifier_mapping_to_ram_channels = array();
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

				if(count(array_unique($identifier_mapping_to_cores)) > 1 || count(array_unique($identifier_mapping_to_threads)) > 1)
				{
					$analyze_checkboxes['Additional Graphs'][] = array('sppt', 'Show Perf Per Core/Thread Calculation Graphs Where Applicable');
				}
				if(count(array_unique($identifier_mapping_to_cpu_clock)) > 1)
				{
					$analyze_checkboxes['Additional Graphs'][] = array('sppc', 'Show Perf Per Clock Calculation Graphs Where Applicable');
				}
				if(count(array_unique($identifier_mapping_to_ram_channels)) > 1)
				{
					$analyze_checkboxes['Additional Graphs'][] = array('sppm', 'Show Perf Per RAM Channel Calculation Graphs Where Applicable');
				}
			}
		}
		if(count($suites_in_result_file) > 1)
		{
			$suite_limit = '<h3>Limit displaying results to tests within:</h3>';
			$stis = self::check_request_for_var($request, 'stis');
			if(!is_array($stis))
			{
				$stis = explode(',', $stis);
			}
			ksort($suites_in_result_file);
			$suite_limit .= '<div style="max-height: 250px; overflow: scroll;">';
			foreach($suites_in_result_file as $suite_identifier => $s)
			{
				list($suite, $contained_tests) = $s;
				$id = rtrim(base64_encode($suite_identifier), '=');
				$suite_limit .= '<input type="checkbox" name="stis[]" value="' . $id . '"' . (is_array($stis) && in_array($id, $stis) ? ' checked="checked"' : null) . ' /> ' . $suite->get_title() . ' <sup><em>' . count($contained_tests) . ' Tests</em></sup><br />';
			}
			$suite_limit .= '</div>';
			$analyze_checkboxes['View'][] = array('', $suite_limit);
		}

		$analyze_checkboxes['Graph Settings'][] = array('vb', 'Prefer Vertical Bar Graphs');
		$analyze_checkboxes['Statistics'][] = array('rol', 'Remove Outliers Before Calculating Averages');
		//$analyze_checkboxes['Statistics'][] = array('gtb', 'Graph Values Of All Runs (Box Plot)');
		//$analyze_checkboxes['Statistics'][] = array('gtl', 'Graph Values Of All Runs (Line Graph)');

		if($has_box_plot || $has_line_graph)
		{
			$analyze_checkboxes['Graph Settings'][] = array('nbp', 'No Box Plots');
		}
		if($has_line_graph || $is_multi_way)
		{
			$analyze_checkboxes['Graph Settings'][] = array('clg', 'On Line Graphs With Missing Data, Connect The Line Gaps');
		}

		if($is_multi_way && $system_count > 1)
		{
			$analyze_checkboxes['Multi-Way Comparison'][] = array('cmw', 'Condense Comparison');
		}
		if(($is_multi_way && $system_count > 1) || self::check_request_for_var($request, 'cmv') || self::check_request_for_var($request, 'cts'))
		{
			$analyze_checkboxes['Multi-Way Comparison'][] = array('imw', 'Transpose Comparison');
		}
		if((!$is_multi_way && $has_test_with_multiple_options && !self::check_request_for_var($request, 'cmv')) || self::check_request_for_var($request, 'cts'))
		{
			$analyze_checkboxes['Multi-Way Comparison'][] = array('cts', 'Condense Multi-Option Tests Into Single Result Graphs');
		}
		if((!$is_multi_way && $has_test_with_multiple_versions && !self::check_request_for_var($request, 'cts')) || self::check_request_for_var($request, 'cmv'))
		{
			$analyze_checkboxes['Multi-Way Comparison'][] = array('cmv', 'Condense Test Profiles With Multiple Version Results Into Single Result Graphs');
		}

		$analyze_checkboxes['Table'][] = array('sdt', 'Show Detailed System Result Table');

		$result_file_env_vars = pts_strings::parse_value_string_vars($result_file->get_preset_environment_variables());
		if(isset($result_file_env_vars['MONITOR']))
		{
			$analyze_checkboxes['Sensor Monitoring'] = array(
				array('asm', 'Show Accumulated Sensor Monitoring Data For Displayed Results')
				);
			if(stripos($result_file_env_vars['MONITOR'], '.power') !== false)
			{
				$analyze_checkboxes['Sensor Monitoring'][] = array('ppw', 'Generate Power Efficiency / Performance Per Watt Results');
			}
		}

		$t = null;
		foreach($analyze_checkboxes as $title => $group)
		{
			if(empty($group))
			{
				continue;
			}
			$t .= '<div class="pts_result_viewer_settings_box">';
			$t .= '<h2>' . $title . '</h2>';
			foreach($group as $key)
			{
				if($key[0] == null)
				{
					$t .= $key[1] . '<br />';
				}
				else
				{
					$t .= '<input type="checkbox" name="' . $key[0] . '" value="1"' . (self::check_request_for_var($request, $key[0]) ? ' checked="checked"' : null) . ' /> ' . $key[1] . '<br />';
				}
			}
			$t .= '</div>';
		}

		if($system_count > 0)
		{
			$has_system_logs = $result_file->system_logs_available();
			$t .= '<div style="clear: both;"><h2>Run Management</h2>
			<div class="div_table">
			<div class="div_table_body">
			<div class="div_table_first_row">';

			if($system_count > 1)
			{
				$t .= '<div class="div_table_cell">Highlight<br />Result</div>
			<div class="div_table_cell">Toggle/Hide<br />Result</div>';
			}

			$t .= '<div class="div_table_cell">Result<br />Identifier</div>';

			if($has_system_logs)
			{
				$t .= '<div class="div_table_cell">View Logs</div>';
			}

			$t .= '<div class="div_table_cell">Performance Per<br />Dollar</div>
			<div class="div_table_cell">Date<br />Run</div>
			<div class="div_table_cell"> &nbsp; Test<br /> &nbsp; Duration</div>
			<div class="div_table_cell"> </div>
			</div>
			';
			$hgv = self::check_request_for_var($request, 'hgv');
			if(!is_array($hgv))
			{
				$hgv = explode(',', $hgv);
			}
			$rmm = self::check_request_for_var($request, 'rmm');
			$rmm_is_array = is_array($rmm);
			if(!$rmm_is_array)
			{
				$rmm .= ',';
			}
			$start_of_year = strtotime(date('Y-01-01'));
			$test_run_times = $result_file->get_test_run_times();
			foreach($result_file->get_systems() as $sys)
			{
				$si = $sys->get_identifier();
				$ppdx = rtrim(base64_encode($si), '=');
				$ppd = self::check_request_for_var($request, 'ppd_' . $ppdx);
				$ppd = is_numeric($ppd) && $ppd > 0 ? $ppd : 0;

			$t .= '
				<div id="table-line-' . $ppdx . '" class="div_table_row">';
				if($system_count > 1)
				{
					$t .= '<div class="div_table_cell"><input type="checkbox" name="hgv[]" value="' . $si . '"' . (is_array($hgv) && in_array($si, $hgv) ? ' checked="checked"' : null) . ' /></div>
				<div class="div_table_cell"><input type="checkbox" name="rmm[]" value="' . $si . '"' . (($rmm_is_array && in_array($si, $rmm)) || (!$rmm_is_array && strpos($rmm, $si . ',') !== false) ? ' checked="checked"' : null) . ' /></div>';
				}

				$t .= '<div class="div_table_cell"><strong>' . $si . '</strong></div>';

				if($has_system_logs)
				{
					$t .= '<div class="div_table_cell">' . ($system_count == 1 || $sys->has_log_files() ? '<button type="button" onclick="javascript:display_system_logs_for_result(\'' . $public_id . '\', \'' . $sys->get_original_identifier() . '\'); return false;">View System Logs</button>' : ' ') . '</div>';
				}
				$stime = strtotime($sys->get_timestamp());
				$t .= '<div class="div_table_cell"><input type="number" min="0" step="0.001" name="ppd_' . $ppdx . '" value="' . ($ppd && $ppd !== true ? strip_tags($ppd) : '0') . '" /></div>
			<div class="div_table_cell">' . date(($stime > $start_of_year ? 'F d' : 'F d Y'), $stime) . '</div>
			<div class="div_table_cell"> &nbsp; ' . (isset($test_run_times[$si]) && $test_run_times[$si] > 0 && $test_run_times[$si] < 604800 ? pts_strings::format_time($test_run_times[$si], 'SECONDS', true, 60) : ' ') . '</div>';

				if($can_delete_results && !empty($public_id))
				{
					$t .= '<div class="div_table_cell">';
					if($system_count > 1)
					{
						$t .= '<button type="button" onclick="javascript:delete_run_from_result_file(\'' . $public_id . '\', \'' . $si . '\', \'' . $ppdx . '\'); return false;">Delete Run</button> ';
					}

					$t .= '<button type="button" onclick="javascript:rename_run_in_result_file(\'' . $public_id . '\', \'' . $si . '\'); return false;">Rename Run</button></div>';
				}
				$t .= '</div>';
			}

			if($system_count > 1)
			{
				$t .= '
				<div class="div_table_row">
				<div class="div_table_cell"> </div>
				<div class="div_table_cell"><input type="checkbox" name="rmmi" value="1"' . (self::check_request_for_var($request, 'rmmi') ? ' checked="checked"' : null) . ' /></div>
				<div class="div_table_cell"><em>Invert Behavior (Only Show Selected Data)</em></div>';

				if($has_system_logs)
				{
					$t .= '<div class="div_table_cell"> </div>';
				}

				$avg_run_time = array_sum($test_run_times) / count($test_run_times);
				$t .= '<div class="div_table_cell">' . self::html_select_menu('ppt', 'ppt', null, array('D' => 'Dollar', 'DPH' => 'Dollar / Hour'), true) . '</div>
				<div class="div_table_cell"> </div>
				<div class="div_table_cell"> &nbsp; <em>' . ($avg_run_time > 0 && $avg_run_time < 604800 ? pts_strings::format_time($avg_run_time, 'SECONDS', true, 60) : '') . '</em></div>
				<div class="div_table_cell">';

				if($can_delete_results)
				{
					$t .= '<button type="button" onclick="javascript:reorder_result_file(\'' . $public_id . '\'); return false;">Sort / Reorder Runs</button>';
				}
				$t .= '</div></div>';
			}

			$t .= '
			</div>
			</div></div>';
		}

		$analyze_options .= $t;

		if($system_count > 2)
		{
			$analyze_options .= '<br /><div>Only show results where ' . self::html_select_menu('ftt', 'ftt', null, array_merge(array(null), $result_file->get_system_identifiers()), false) . ' is faster than ' . self::html_select_menu('ftb', 'ftb', null, array_merge(array(null), $result_file->get_system_identifiers()), false) . '</div>';
		}

		if($result_file->get_test_count() > 1)
		{
			$analyze_options .= '<div>Only show results matching title/arguments (delimit multiple options with a comma): ' . self::html_input_field('oss', 'oss') . '</div>';
			$analyze_options .= '<div>Do not show results matching title/arguments (delimit multiple options with a comma): ' . self::html_input_field('noss', 'noss') . '</div>';
		}

		$analyze_options .= '<br /><input style="clear: both;" name="submit" value="Refresh Results" type="submit" /></form>';

		return $analyze_options;
	}
	public static function process_result_export_pre_render(&$request, &$result_file, &$extra_attributes, $can_modify_results = false, $can_delete_results = false)
	{
		if(self::check_request_for_var($request, 'rdt'))
		{
			$result_file->reorder_runs($result_file->get_system_identifiers_by_date());
		}

		// Result export?
		$result_title = (isset($_GET['result']) ? str_replace(',', '_', $_GET['result']) : 'result');
		switch(isset($_REQUEST['export']) ? $_REQUEST['export'] : '')
		{
			case '':
				break;
			case 'pdf':
				header('Content-Type: application/pdf');
				$pdf_output = pts_result_file_output::result_file_to_pdf($result_file, $result_title . '.pdf', 'D', $extra_attributes);
				exit;
			case 'html':
				$referral_url = '';
				if(defined('OPENBENCHMARKING_BUILD'))
				{
					$referral_url = 'https://openbenchmarking.org' . str_replace('&export=html', '', $_SERVER['REQUEST_URI']);
				}
				echo pts_result_file_output::result_file_to_html($result_file, $extra_attributes, $referral_url);
				exit;
			case 'json':
				header('Content-Type: application/json');
				echo pts_result_file_output::result_file_to_json($result_file);
				exit;
			case 'csv':
				$result_csv = pts_result_file_output::result_file_to_csv($result_file, ',', $extra_attributes);
				header('Content-Description: File Transfer');
				header('Content-Type: application/csv');
				header('Content-Disposition: attachment; filename=' . $result_title . '.csv');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . strlen($result_csv));
				echo $result_csv;
				exit;
			case 'csv-all':
				$result_csv = pts_result_file_output::result_file_raw_to_csv($result_file);
				header('Content-Description: File Transfer');
				header('Content-Type: application/csv');
				header('Content-Disposition: attachment; filename=' . $result_title . '.csv');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . strlen($result_csv));
				echo $result_csv;
				exit;
			case 'txt':
				$result_txt = pts_result_file_output::result_file_to_text($result_file);
				header('Content-Description: File Transfer');
				header('Content-Type: text/plain');
				header('Content-Disposition: attachment; filename=' . $result_title . '.txt');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . strlen($result_txt));
				echo $result_txt;
				exit;
			case 'xml-suite':
				$suite_xml = pts_result_file_output::result_file_to_suite_xml($result_file);
				header('Content-Description: File Transfer');
				header('Content-Type: text/xml');
				header('Content-Disposition: attachment; filename=' . $result_title . '-suite.xml');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . strlen($suite_xml));
				echo $suite_xml;
				exit;
			case 'xml':
				$result_xml = $result_file->get_xml(null, true);
				header('Content-Description: File Transfer');
				header('Content-Type: text/xml');
				header('Content-Disposition: attachment; filename=' . $result_title . '.xml');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . strlen($result_xml));
				echo $result_xml;
				exit;
			case 'view_system_logs':
				$html_viewer = '';
				foreach($result_file->get_systems() as $system)
				{
					$sid = base64_decode($_REQUEST['system_id']);

					if($system->get_original_identifier() == $sid || $system->get_identifier() == $sid)
					{
						$system_logs = $system->log_files();
						$identifiers_with_logs = empty($system_logs) ? $result_file->identifiers_with_system_logs() : array();
						$show_log = isset($_REQUEST['log_select']) && $_REQUEST['log_select'] != 'undefined' && $_REQUEST['log_select'] != null ? $_REQUEST['log_select'] : (isset($system_logs[0]) ? $system_logs[0] : '');
						$log_contents = $system->log_files($show_log, false);
						pts_result_viewer_embed::display_log_html_or_download($log_contents, $system_logs, $show_log, $html_viewer, $sid, $identifiers_with_logs);
						break;
					}
				}
				echo pts_result_viewer_embed::html_template_log_viewer($html_viewer, $result_file);
				exit;
			case 'view_install_logs':
				$html_viewer = '';
				if(isset($_REQUEST['result_object']))
				{
					if(($result_object = $result_file->get_result_object_by_hash($_REQUEST['result_object'])))
					{
						$install_logs = $result_file->get_install_log_for_test($result_object->test_profile, false);
						if(count($install_logs) > 0)
						{
							$show_log = isset($_REQUEST['log_select']) && $_REQUEST['log_select'] != 'undefined' ? $_REQUEST['log_select'] : (isset($install_logs[0]) ? $install_logs[0] : '');
							$log_contents = $result_file->get_install_log_for_test($result_object->test_profile, $show_log, false);
							pts_result_viewer_embed::display_log_html_or_download($log_contents, $install_logs, $show_log, $html_viewer, $result_object->test_profile->get_title() . ' Installation');
						}
					}
				}
				echo pts_result_viewer_embed::html_template_log_viewer($html_viewer, $result_file);
				exit;
			case 'view_test_logs':
				$html_viewer = '';
				if(isset($_REQUEST['result_object']))
				{
					if(($result_object = $result_file->get_result_object_by_hash($_REQUEST['result_object'])))
					{
						if(($test_logs = $result_file->get_test_run_log_for_result($result_object, false)))
						{
							$show_log = isset($_REQUEST['log_select']) && $_REQUEST['log_select'] != 'undefined' ? $_REQUEST['log_select'] : (isset($test_logs[0]) ? $test_logs[0] : '');
							$log_contents = $result_file->get_test_run_log_for_result($result_object, $show_log, false);
							pts_result_viewer_embed::display_log_html_or_download($log_contents, $test_logs, $show_log, $html_viewer, trim($result_object->test_profile->get_title() . ' ' . $result_object->get_arguments_description()));
						}
					}

				}
				echo pts_result_viewer_embed::html_template_log_viewer($html_viewer, $result_file);
				exit;
		}
	}
	public static function process_result_modify_pre_render(&$result_file, $can_modify_results = false, $can_delete_results = false)
	{
		if(!isset($_REQUEST['modify']) || ($can_modify_results == false && $can_delete_results == false))
		{
			return;
		}

		switch($_REQUEST['modify'])
		{
			case 'update-result-file-meta':
				if($can_modify_results && isset($_REQUEST['result_title']) && isset($_REQUEST['result_desc']))
				{
					$result_file->set_title($_REQUEST['result_title']);
					$result_file->set_description($_REQUEST['result_desc']);
					$result_file->save();
				}
				exit;
			case 'remove-result-object':
				if($can_delete_results && isset($_REQUEST['result_object']))
				{
					if($result_file->remove_result_object_by_id($_REQUEST['result_object']))
					{
						$result_file->save();
					}
				}
				exit;
			case 'remove-result-run':
				if($can_delete_results && isset($_REQUEST['result_run']))
				{
					if($result_file->remove_run($_REQUEST['result_run']))
					{
						$result_file->save();
					}
				}
				exit;
			case 'rename-result-run':
				if(VIEWER_CAN_MODIFY_RESULTS && isset($_REQUEST['result_run']) && isset($_REQUEST['new_result_run']))
				{
					if($result_file->rename_run($_REQUEST['result_run'], $_REQUEST['new_result_run']))
					{
						$result_file->save();
					}
				}
				exit;
			case 'add-annotation-to-result-object':
				if($can_modify_results && isset($_REQUEST['result_object']) && isset($_REQUEST['annotation']))
				{
					if($result_file->update_annotation_for_result_object_by_id($_REQUEST['result_object'], $_REQUEST['annotation']))
					{
						$result_file->save();
					}
				}
				exit;
			case 'reorder_result_file':
				if($can_modify_results)
				{
					if(count($result_file_identifiers = $result_file->get_system_identifiers()) > 1)
					{
						if(isset($_POST['reorder_post']))
						{
							$sort_array = array();

							foreach($result_file_identifiers as $i => $id)
							{
								if(isset($_POST[base64_encode($id)]))
								{
									$sort_array[$id] = $_POST[base64_encode($id)];
								}
							}
							asort($sort_array);
							$sort_array = array_keys($sort_array);
							$result_file->reorder_runs($sort_array);
							$result_file->save();
							echo '<p>Result file is now reordered. <script> window.close(); </script></p>';
						}
						else if(isset($_POST['auto_sort']))
						{
							sort($result_file_identifiers);
							$result_file->reorder_runs($result_file_identifiers);
							$result_file->save();
							echo '<p>Result file is now auto-sorted. <script> window.close(); </script></p>';
						}
						else
						{
							echo '<p>Reorder the result file as desired by altering the numbering from lowest to highest.</p>';
							echo '<form method="post" action="' . $_SERVER['REQUEST_URI'] . '">';
							foreach($result_file_identifiers as $i => $id)
							{
								echo '<input style="width: 80px;" name="' . base64_encode($id) . '" type="number" min="0" value="' . ($i + 1) . '" />' . $id . '<br />';
							}
							echo '<input type="hidden" name="reorder_post" value="1" /><input type="submit" value="Reorder Results" /></form>';
							echo '<form method="post" action="' . $_SERVER['REQUEST_URI'] . '">';

							echo '<input type="hidden" name="auto_sort" value="1" /><input type="submit" value="Auto-Sort Result File" /></form>';
						}
					}
				}
				exit;
		}
	}
	public static function process_helper_html(&$request, &$result_file, &$extra_attributes, $can_modify_results = false, $can_delete_results = false)
	{
		self::process_result_export_pre_render($request, $result_file, $extra_attributes, $can_modify_results, $can_delete_results);
		$html = null;
		if(self::check_request_for_var($request, 'spr'))
		{
			$spreads = array();
			foreach($result_file->get_result_object_keys() as $ro_key)
			{
				$result_object = $result_file->get_result_object_by_hash($ro_key);
				if($result_object)
				{
					$spreads[$ro_key] = $result_object->get_spread();
				}
			}
			arsort($spreads);
			$spreads = array_slice($spreads, 0, min((int)($result_file->get_test_count() / 4), 10), true);

			if(!empty($spreads))
			{
				$html .= '<h3>Notable Results</h3>';
				foreach($spreads as $result_key => $spread)
				{
					$ro = $result_file->get_result_object_by_hash($result_key);
					if(!is_object($ro[0]))
					{
						continue;
					}
					$html .= '<a href="#r-' . $result_key . '">' . $ro[0]->test_profile->get_title() . ' - ' . $ro[0]->get_arguments_description() . '</a><br />';
				}
			}
		}
		return $html;
	}
	public static function check_request_for_var(&$request, $check)
	{
		// the obr_ check is to maintain OpenBenchmarking.org compatibility for its original variable naming to preserve existing URLs
		$ret = false;
		if(defined('OPENBENCHMARKING_BUILD') && isset($request['obr_' . $check]))
		{
			$ret = empty($request['obr_' . $check]) ? true : $request['obr_' . $check];
		}
		if(isset($request[$check]))
		{
			$ret = empty($request[$check]) ? true : $request[$check];
		}

		if($ret && isset($ret[5]))
		{
			$ret = str_replace('_DD_', '.', $ret);
		}

		foreach(pts_strings::safety_strings_to_reject() as $invalid_string)
		{
			if(stripos($ret, $invalid_string) !== false)
			{
				echo '<strong>Exited due to invalid input ( ' . $invalid_string . ') attempted:</strong> ' . htmlspecialchars($ret);
				exit;
			}
		}

		return $ret;
	}
	public static function process_request_to_attributes(&$request, &$result_file, &$extra_attributes)
	{
		if(($oss = self::check_request_for_var($request, 'oss')) && strlen($oss) > 1)
		{
			$oss = pts_strings::comma_explode($oss);
			foreach($result_file->get_result_object_keys() as &$ro_key)
			{
				$result_object = $result_file->get_result_object_by_hash($ro_key);
				if($result_object == false)
				{
					continue;
				}
				$matched = false;
				foreach($oss as $search_check)
				{
					if(stripos($result_object->get_arguments_description(), $search_check) === false && stripos($result_object->test_profile->get_identifier(), $search_check) === false && stripos($result_object->test_profile->get_title(), $search_check) === false)
					{
						// Not found
						$matched = false;
					}
					else
					{
						$matched = true;
						break;
					}
				}
				if(!$matched)
				{
					$result_file->remove_result_object_by_id($ro_key);
				}
			}
		}
		if(($noss = self::check_request_for_var($request, 'noss')) && strlen($noss) > 1)
		{
			$noss = pts_strings::comma_explode($noss);
			foreach($result_file->get_result_object_keys() as &$ro_key)
			{
				$result_object = $result_file->get_result_object_by_hash($ro_key);
				if($result_object == false)
				{
					continue;
				}
				$matched = false;
				foreach($noss as $search_check)
				{
					if(stripos($result_object->get_arguments_description(), $search_check) === false && stripos($result_object->test_profile->get_identifier(), $search_check) === false && stripos($result_object->test_profile->get_title(), $search_check) === false)
					{
						// Not found
						$matched = false;
					}
					else
					{
						$matched = true;
						break;
					}
				}
				if($matched)
				{
					$result_file->remove_result_object_by_id($ro_key);
				}
			}
		}
		if(self::check_request_for_var($request, 'ftt') && self::check_request_for_var($request, 'ftt'))
		{
			$ftt = self::check_request_for_var($request, 'ftt');
			$ftb = self::check_request_for_var($request, 'ftb');
			if(!empty($ftt) && !empty($ftb) && $ftt !== true && $ftb !== true)
			{
				foreach($result_file->get_result_object_keys() as &$ro_key)
				{
					$result_object = $result_file->get_result_object_by_hash($ro_key);
					if($result_object == false)
					{
						continue;
					}
					$ftt_result = $result_object->test_result_buffer->get_result_from_identifier($ftt);
					$ftb_result = $result_object->test_result_buffer->get_result_from_identifier($ftb);

					if($ftt_result && $ftb_result)
					{
						$ftt_wins = false;

						if($result_object->test_profile->get_result_proportion() == 'HIB')
						{
							if($ftt_result > $ftb_result)
							{
								$ftt_wins = true;
							}
						}
						else
						{
							if($ftt_result < $ftb_result)
							{
								$ftt_wins = true;
							}
						}

						if(!$ftt_wins)
						{
							$result_file->remove_result_object_by_id($ro_key);
						}
					}
					else
					{
						$result_file->remove_result_object_by_id($ro_key);
					}
				}
			}
		}
		if(($stis = self::check_request_for_var($request, 'stis')))
		{
			if(!is_array($stis))
			{
				$stis = explode(',', $stis);
			}
			$suites_in_result_file = pts_test_suites::suites_in_result_file($result_file, true, 0);
			$tests_to_show = array();
			foreach($stis as $suite_to_show)
			{
				$suite_to_show = base64_decode($suite_to_show);
				if(isset($suites_in_result_file[$suite_to_show]))
				{
					foreach($suites_in_result_file[$suite_to_show][1] as $test_to_show)
					{
						$tests_to_show[] = $test_to_show;
					}
				}
			}

			if(!empty($tests_to_show))
			{
				foreach($result_file->get_result_object_keys() as &$ro_key)
				{
					$result_object = $result_file->get_result_object_by_hash($ro_key);
					if($result_object == false)
					{
						continue;
					}
					if($result_object->get_parent_hash())
					{
						if(!$result_file->get_result_object_by_hash($result_object->get_parent_hash()) || !in_array($result_file->get_result_object_by_hash($result_object->get_parent_hash())->test_profile->get_identifier(false), $tests_to_show))
						{
							$result_file->remove_result_object_by_id($ro_key);
						}
					}
					else if(!in_array($result_object->test_profile->get_identifier(false), $tests_to_show))
					{
						$result_file->remove_result_object_by_id($ro_key);
					}
				}
			}
		}
		if(self::check_request_for_var($request, 'hlc'))
		{
			foreach($result_file->get_result_object_keys() as &$ro_key)
			{
				$result_object = $result_file->get_result_object_by_hash($ro_key);
				if($result_object == false)
				{
					continue;
				}
				if($result_object->result_flat())
				{
					$result_file->remove_result_object_by_id($ro_key);
				}
			}
		}
		if(self::check_request_for_var($request, 'hnr'))
		{
			$result_file->remove_noisy_results();
		}
		if(self::check_request_for_var($request, 'hni'))
		{
			$system_count = $result_file->get_system_count();
			foreach($result_file->get_result_object_keys() as $ro_key)
			{
				$result_object = $result_file->get_result_object_by_hash($ro_key);
				if($result_object == false)
				{
					continue;
				}
				if($result_object->test_result_buffer->get_count() < $system_count || $result_object->test_result_buffer->has_incomplete_result())
				{
					$result_file->remove_result_object_by_id($ro_key);
				}
			}
		}
		if(($rmm = self::check_request_for_var($request, 'rmm')))
		{
			if(self::check_request_for_var($request, 'rmmi'))
			{
				// Invert behavior
				$rmm_is_array = is_array($rmm);
				if(!$rmm_is_array)
				{
					$rmm .= ',';
				}

				foreach($result_file->get_system_identifiers() as $si)
				{
					if(($rmm_is_array && !in_array($si, $rmm)) || (!$rmm_is_array && strpos($rmm, $si . ',') === false))
					{
						$result_file->remove_run($si);
					}
				}
			}
			else
			{
				if(!is_array($rmm))
				{
					$rmm = explode(',', $rmm);
				}
				foreach($rmm as $rm)
				{
					$result_file->remove_run($rm);
				}
			}
		}

		if(self::check_request_for_var($request, 'asm') || self::check_request_for_var($request, 'ppw'))
		{
			$results = pts_result_file_analyzer::generate_composite_for_sensors($result_file, false, (self::check_request_for_var($request, 'ppw') ? 'Power' : false), (self::check_request_for_var($request, 'asm') ? true : false));
			if($results)
			{
				foreach($results as $result)
				{
					$result_file->add_result($result);
				}
			}
		}
		if(self::check_request_for_var($request, 'grs'))
		{
			$result_file->sort_result_object_order_by_spread();
		}
		else if(self::check_request_for_var($request, 'grt'))
		{
			$result_file->sort_result_object_order_by_title();
		}
		else if(self::check_request_for_var($request, 'grw'))
		{
			$result_file->sort_result_object_order_by_common_suites_workloads();
		}
		else if(self::check_request_for_var($request, 'gru'))
		{
			$result_file->sort_result_object_order_by_result_scale();
		}
		else if(self::check_request_for_var($request, 'grr'))
		{
			$result_file->sort_result_object_order_by_run_time();
		}

		if(self::check_request_for_var($request, 'shm'))
		{
			foreach(pts_result_file_analyzer::generate_harmonic_mean_result($result_file) as $result)
			{
				if($result)
				{
					$result_file->add_result($result);
				}
			}
		}
		if(self::check_request_for_var($request, 'sgm'))
		{
			$result = pts_result_file_analyzer::generate_geometric_mean_result($result_file);
			if($result)
			{
				$result_file->add_result($result);
			}
		}
		if(self::check_request_for_var($request, 'sts'))
		{
			foreach(pts_result_file_analyzer::generate_geometric_mean_result_for_suites_in_result_file($result_file, true, 0) as $result)
			{
				if($result)
				{
					$result_file->add_result($result);
				}
			}
		}
		if(self::check_request_for_var($request, 'swl'))
		{
			foreach(pts_result_file_analyzer::generate_wins_losses_results($result_file) as $result)
			{
				if($result)
				{
					$result_file->add_result($result);
				}
			}
		}
		if(self::check_request_for_var($request, 'cts'))
		{
			pts_result_file_analyzer::condense_result_file_by_multi_option_tests($result_file);
		}
		if(self::check_request_for_var($request, 'cmv'))
		{
			pts_result_file_analyzer::condense_result_file_by_multi_version_tests($result_file);
		}
		if(self::check_request_for_var($request, 'sor'))
		{
			$extra_attributes['sort_result_buffer_values'] = true;
		}
		if(self::check_request_for_var($request, 'rro'))
		{
			$extra_attributes['reverse_result_buffer'] = true;
		}
		if(self::check_request_for_var($request, 'sro'))
		{
			$extra_attributes['sort_result_buffer'] = true;
		}
		if(self::check_request_for_var($request, 'nor'))
		{
			$extra_attributes['normalize_result_buffer'] = true;
		}
		if(self::check_request_for_var($request, 'ftr'))
		{
			$extra_attributes['force_tracking_line_graph'] = true;
		}
		if(self::check_request_for_var($request, 'imw'))
		{
			$extra_attributes['multi_way_comparison_invert_default'] = false;
		}
		if(self::check_request_for_var($request, 'cmw'))
		{
			$extra_attributes['condense_multi_way'] = true;
		}
		if(($hgv = self::check_request_for_var($request, 'hgv')))
		{
			if(is_array($hgv))
			{
				$extra_attributes['highlight_graph_values'] = $hgv;
			}
			else
			{
				$extra_attributes['highlight_graph_values'] = explode(',', $hgv);
			}
		}
		else if(self::check_request_for_var($request, 'hgv_base64'))
		{
			$extra_attributes['highlight_graph_values'] = explode(',', base64_decode(self::check_request_for_var($request, 'hgv_base64')));
		}
		if(self::check_request_for_var($request, 'scalar'))
		{
			$extra_attributes['compact_to_scalar'] = true;
		}
		if(self::check_request_for_var($request, 'ncb'))
		{
			$extra_attributes['no_color_branding'] = true;
		}
		if(self::check_request_for_var($request, 'nbp'))
		{
			$extra_attributes['no_box_plots'] = true;
		}
		if(self::check_request_for_var($request, 'clg'))
		{
			$extra_attributes['on_zero_plot_connect'] = true;
		}
		if(self::check_request_for_var($request, 'vb'))
		{
			$extra_attributes['vertical_bars'] = true;
		}
		/*
		if(self::check_request_for_var($request, 'gtb'))
		{
			$extra_attributes['graph_render_type'] = 'HORIZONTAL_BOX_PLOT';
		}
		else if(self::check_request_for_var($request, 'gtl'))
		{
			$extra_attributes['graph_render_type'] = 'LINE_GRAPH';
			$extra_attributes['graph_raw_values'] = true;
		}
		*/
		if(self::check_request_for_var($request, 'rol'))
		{
			foreach($result_file->get_result_object_keys() as $ro_key)
			{
				$result_object = $result_file->get_result_object_by_hash($ro_key);
				if($result_object)
				{
					$result_object->recalculate_averages_without_outliers(1.5);
				}
			}
		}

		$perf_per_dollar_values = array();
		foreach($result_file->get_system_identifiers() as $si)
		{
			$ppd = self::check_request_for_var($request, 'ppd_' . rtrim(base64_encode($si), '='));
			if($ppd && $ppd > 0 && is_numeric($ppd))
			{
				$perf_per_dollar_values[$si] = $ppd;
			}
		}

		if(!empty($perf_per_dollar_values))
		{
			$perf_per_hour = self::check_request_for_var($request, 'ppt') == 'DPH';
			pts_result_file_analyzer::generate_perf_per_dollar($result_file, $perf_per_dollar_values, 'Dollar', false, $perf_per_hour);
		}
	}
	public static function html_input_field($name, $id, $on_change = null)
	{
		return '<input type="text" name="' . pts_strings::simple($name) . '" id="' . pts_strings::simple($id) . '" onclick="" value="' . (isset($_REQUEST[$name]) ? pts_strings::sanitize(strip_tags($_REQUEST[$name])) : null) . '">';
	}
	public static function html_select_menu($name, $id, $on_change, $elements, $use_index = true, $other_attributes = array(), $selected = -1)
	{
		$tag = null;
		foreach($other_attributes as $i => $v)
		{
			$tag .= ' ' . $i . '="' . $v . '"';
		}

		$html_menu = '<select name="' . $name . '" id="' . $id . '" onchange="' . $on_change . '"' . $tag . '>' . PHP_EOL;

		if($selected === -1)
		{
			$selected = isset($_REQUEST[$name]) && !empty($_REQUEST[$name]) ? $_REQUEST[$name] : false;
		}

		$force_select = isset($other_attributes['multiple']);

		foreach($elements as $value => $name)
		{
			if($use_index == false)
			{
				$value = $name;
			}
			if($name == null)
			{
				$name = '[SELECT]';
			}

			$html_menu .= '<option value="' . $value . '"' . ($value == $selected || $force_select ? ' selected="selected"' : null) . '>' . $name . '</option>';
		}

		$html_menu .= '</select>';

		return $html_menu;
	}
}

?>
