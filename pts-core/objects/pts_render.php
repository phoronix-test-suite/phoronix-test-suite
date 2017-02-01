<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2017, Phoronix Media
	Copyright (C) 2008 - 2017, Michael Larabel

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

class pts_render
{
	public static function render_graph(&$result_object, &$result_file = null, $save_as = false, $extra_attributes = null)
	{
		$graph = self::render_graph_process($result_object, $result_file, $save_as, $extra_attributes);
		if($graph == false)
		{
			return false;
		}

		$graph->renderGraph();
		return $graph->svg_dom->output($save_as);
	}
	public static function render_graph_inline_embed(&$object, &$result_file = null, $extra_attributes = null, $nested = true, $output_format = 'SVG')
	{
		if($object instanceof pts_test_result)
		{
			$graph = self::render_graph_process($object, $result_file, false, $extra_attributes);
		}
		else if($object instanceof pts_graph_core)
		{
			$graph = $object;
		}
		else
		{
			return false;
		}

		if($graph == false)
		{
			return false;
		}

		$graph->renderGraph();
		$graph = $graph->svg_dom->output(null, $output_format);

		switch($output_format)
		{
			case 'PNG':
			case 'JPG':
				if($nested)
				{
					$graph = '<img src="data:image/png;base64,' . base64_encode($graph) . '" />';
				}
				else
				{
					header('Content-Type: image/' . strtolower($output_format));
				}
				break;
			case 'HTML':
				break;
			default:
			case 'SVG':
				if($nested)
				{
					// strip out any DOCTYPE and other crud that would be redundant, so start at SVG tag
					$graph = substr($graph, strpos($graph, '<svg'));
				}
				else
				{
					header('Content-type: image/svg+xml');
				}
				break;
		}

		return $graph;
	}
	public static function render_graph_process(&$result_object, &$result_file = null, $save_as = false, $extra_attributes = null)
	{
		// NOTICE: $save_as doesn't appear used anymore
		if(isset($extra_attributes['clear_unchanged_results']))
		{
			$result_object->remove_unchanged_results();
		}
		if(isset($extra_attributes['clear_noisy_results']))
		{
			$result_object->remove_noisy_results();
		}
		if(isset($extra_attributes['sort_result_buffer']))
		{
			$result_object->test_result_buffer->sort_buffer_items();
		}
		if(isset($extra_attributes['normalize_result_buffer']))
		{
			if(isset($extra_attributes['highlight_graph_values']) && is_array($extra_attributes['highlight_graph_values']) && count($extra_attributes['highlight_graph_values']) == 1)
			{
				$normalize_against = $extra_attributes['highlight_graph_values'][0];
			}
			else
			{
				$normalize_against = false;
			}

			$result_object->normalize_buffer_values($normalize_against);
		}
		if(isset($extra_attributes['sort_result_buffer_values']))
		{
			$result_object->test_result_buffer->buffer_values_sort();

			if($result_object->test_profile->get_result_proportion() == 'HIB')
			{
				$result_object->test_result_buffer->buffer_values_reverse();
			}
		}
		/*if(isset($extra_attributes['remove_noisy_results']))
		{
			foreach($result_object->test_result_buffer->get_buffer_items() as $i => &$buffer_item)
			{

			}
		}*/
		if(isset($extra_attributes['reverse_result_buffer']))
		{
			$result_object->test_result_buffer->buffer_values_reverse();
		}

		if($result_object->test_result_buffer->get_count() == 0)
		{
			return false;
		}

		if($result_file != null)
		{
			// Cache the redundant words on identifiers so it's not re-computed on every graph
			static $redundant_word_cache;

			if(!isset($redundant_word_cache[$result_file->get_title()]))
			{
				$redundant_word_cache[$result_file->get_title()] = self::evaluate_redundant_identifier_words($result_file->get_system_identifiers());
			}

			if($redundant_word_cache[$result_file->get_title()])
			{
				$result_object->test_result_buffer->auto_shorten_buffer_identifiers($redundant_word_cache[$result_file->get_title()]);
			}

			$result_identifiers = $result_object->test_result_buffer->get_identifiers();

			// COMPACT PROCESS
			if(!isset($extra_attributes['compact_to_scalar']) && $result_object->test_profile->get_display_format() == 'LINE_GRAPH' && ($result_file->get_system_count() > 7 || $result_file->is_multi_way_comparison($result_identifiers, $extra_attributes)))
			{
				// If there's too many lines being plotted on line graph, likely to look messy, so convert to scalar automatically
				$extra_attributes['compact_to_scalar'] = true;
			}

			// XXX: removed || $result_file->is_results_tracker() from below and should be added
			// Removing the command fixes cases like: 1210053-BY-MYRESULTS43
			if(isset($extra_attributes['compact_to_scalar']) || isset($extra_attributes['compact_scatter']) || $result_file->is_multi_way_comparison($result_identifiers, $extra_attributes))
			{
				if((isset($extra_attributes['compact_to_scalar']) || (false && $result_file->is_multi_way_comparison($result_identifiers, $extra_attributes))) && in_array($result_object->test_profile->get_display_format(), array('LINE_GRAPH', 'FILLED_LINE_GRAPH')))
				{
					// Convert multi-way line graph into horizontal box plot
					if(true) // XXX is there any cases where we don't want horizontal box plot but prefer averaged bar graph?
					{
						$result_object->test_profile->set_display_format('HORIZONTAL_BOX_PLOT');
					}
				}

				if($result_file->is_results_tracker() && !isset($extra_attributes['compact_to_scalar']))
				{
					$extra_attributes['force_tracking_line_graph'] = 1;
				}

				if((self::multi_way_identifier_check($result_object->test_result_buffer->get_identifiers()) || (isset($extra_attributes['force_tracking_line_graph']) && $extra_attributes['force_tracking_line_graph'])))
				{
					//$result_table = false;
					//pts_render::compact_result_file_test_object($result_object, $result_table, $result_file, $extra_attributes);
					if($result_object->test_profile->get_display_format() == 'LINE_GRAPH' || (isset($extra_attributes['force_tracking_line_graph']) && $extra_attributes['force_tracking_line_graph']))
					{
						$result_object->test_profile->set_display_format('LINE_GRAPH');
					}
				}
			}
			else if(in_array($result_object->test_profile->get_display_format(), array('LINE_GRAPH')))
			{
					// Check to see for line graphs if every result is an array of the same result (i.e. a flat line for every result).
					// If all the results are just flat lines, you might as well convert it to a bar graph
					$buffer_items = $result_object->test_result_buffer->get_buffer_items();
					$all_values_are_flat = false;
					$flat_values = array();

					foreach($buffer_items as $i => $buffer_item)
					{
						$unique_in_buffer = array_unique(explode(',', $buffer_item->get_result_value()));
						$all_values_are_flat = count($unique_in_buffer) == 1;

						if($all_values_are_flat == false)
						{
							break;
						}
						$flat_values[$i] = array_pop($unique_in_buffer);
					}

					if($all_values_are_flat)
					{
						$result_object->test_result_buffer = new pts_test_result_buffer();
						foreach($buffer_items as $i => $buffer_item)
						{
							$result_object->test_result_buffer->add_test_result($buffer_item->get_result_identifier(), $flat_values[$i]);
						}

						$result_object->test_profile->set_display_format('BAR_GRAPH');
					}
			}
		}

		if(isset($extra_attributes['graph_render_type']))
		{
			$result_object->test_profile->set_display_format($extra_attributes['graph_render_type']);
		}

		switch($result_object->test_profile->get_display_format())
		{
			case 'LINE_GRAPH':
				$graph = new pts_graph_lines($result_object, $result_file, $extra_attributes);
				break;
			case 'HORIZONTAL_BOX_PLOT':
				$graph = new pts_graph_box_plot($result_object, $result_file, $extra_attributes);
				break;
			case 'BAR_ANALYZE_GRAPH':
			case 'BAR_GRAPH':
				$graph = new pts_graph_horizontal_bars($result_object, $result_file, $extra_attributes);
				break;
			case 'PASS_FAIL':
			case 'MULTI_PASS_FAIL':
				$graph = new pts_graph_passfail($result_object, $result_file, $extra_attributes);
				break;
			case 'IMAGE_COMPARISON':
				$graph = new pts_graph_iqc($result_object, $result_file, $extra_attributes);
				break;
			case 'SCATTER_PLOT':
				$graph = new pts_graph_scatter_plot($result_object, $result_file, $extra_attributes);
				break;
			default:

				switch($requested_graph_type)
				{
					case 'LINE_GRAPH':
						$graph = new pts_graph_lines($result_object, $result_file, $extra_attributes);
						break;
					case 'HORIZONTAL_BOX_PLOT':
						$graph = new pts_graph_box_plot($result_object, $result_file, $extra_attributes);
						break;
					default:
						$graph = new pts_graph_horizontal_bars($result_object, $result_file, $extra_attributes);
						break;
				}
				break;

		}

		self::report_test_notes_to_graph($graph, $result_object);

		return $graph;
	}
	public static function report_system_notes_to_table(&$result_file, &$table)
	{
		$identifier_count = 0;
		$system_attributes = array();

		foreach($result_file->get_systems() as $s)
		{
			$json = $s->get_json();
			$identifier = $s->get_identifier();
			$identifier_count++;

			if(isset($json['kernel-parameters']) && $json['kernel-parameters'] != null)
			{
				$system_attributes['Kernel'][$identifier] = $json['kernel-parameters'];
			}
			if(isset($json['environment-variables']) && $json['environment-variables'] != null)
			{
				$system_attributes['Environment'][$identifier] = $json['environment-variables'];
			}
			if(isset($json['compiler-configuration']) && $json['compiler-configuration'] != null)
			{
				$system_attributes['Compiler'][$identifier] = $json['compiler-configuration'];
			}
			if(isset($json['disk-scheduler']) && isset($json['disk-mount-options']))
			{
				$system_attributes['Disk'][$identifier] = $json['disk-scheduler'] . ' / ' . $json['disk-mount-options'];
				if(isset($json['disk-details']) && !empty($json['disk-details']))
				{
					$system_attributes['Disk'][$identifier] .= ' / ' . $json['disk-details'];
				}
			}
			if(isset($json['cpu-scaling-governor']))
			{
				$system_attributes['Processor'][$identifier] = 'Scaling Governor: ' . $json['cpu-scaling-governor'];
			}
			if(isset($json['graphics-2d-acceleration']) || isset($json['graphics-aa']) || isset($json['graphics-af']))
			{
				$report = array();
				foreach(array('graphics-2d-acceleration', 'graphics-aa', 'graphics-af') as $check)
				{
					if(isset($json[$check]) && !empty($json[$check]))
					{
						$report[] = $json[$check];
					}
				}

				$system_attributes['Graphics'][$identifier] = implode(' - ' , $report);
			}
			if(isset($json['graphics-compute-cores']))
			{
				$system_attributes['OpenCL'][$identifier] = 'GPU Compute Cores: ' . $json['graphics-compute-cores'];
			}
		}

		if(isset($system_attributes['compiler']) && count($system_attributes['compiler']) == 1 && ($result_file->get_system_count() > 1 && ($intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true)) && isset($intent[0]) && is_array($intent[0]) && array_shift($intent[0]) == 'Compiler') == false)
		{
			// Only show compiler strings when it's meaningful (since they tend to be long strings)
			unset($system_attributes['compiler']);
		}

		foreach($system_attributes as $index_name => $attributes)
		{
			$unique_attribue_count = count(array_unique($attributes));

			$section = $identifier_count > 1 ? ucwords($index_name) : null;

			switch($unique_attribue_count)
			{
				case 0:
					break;
				case 1:
					if($identifier_count == count($attributes))
					{
						// So there is something for all of the test runs and it's all the same...
						$table->addTestNote(array_pop($attributes), null, $section);
					}
					else
					{
						// There is missing data for some test runs for this value so report the runs this is relevant to.
						$table->addTestNote(implode(', ', array_keys($attributes)) . ': ' . array_pop($attributes), null, $section);
					}
					break;
				default:
					foreach($attributes as $identifier => $configuration)
					{
						$table->addTestNote($identifier . ': ' . $configuration, null, $section);
					}
					break;
			}
		}
	}
	protected static function report_test_notes_to_graph(&$graph, &$result_object)
	{
		// do some magic here to report any test notes....
		$json = array();
		$unique_compiler_data = array();
		foreach($result_object->test_result_buffer->get_buffer_items() as $buffer_item)
		{
			$result_json = $buffer_item->get_result_json();

			if(!empty($result_json))
			{
				$json[$buffer_item->get_result_identifier()] = $result_json;
				if(isset($result_json['compiler-options']) && !empty($result_json['compiler-options']))
				{
					pts_arrays::unique_push($unique_compiler_data, $result_json['compiler-options']);
				}
			}
			// report against graph with $graph->addTestNote($note, $hover_title = null);
		}

		if(empty($json))
		{
			// no JSON data being reported to look at...
			return false;
		}

		if(isset($unique_compiler_data[0]['compiler-type']) && isset($unique_compiler_data[0]['compiler']))
		{
			$compiler_options_string = '(' . strtoupper($unique_compiler_data[0]['compiler-type']) . ') ' . $unique_compiler_data[0]['compiler'] . ' options: ';
		}
		else
		{
			$compiler_options_string = null;
		}

		switch(count($unique_compiler_data))
		{
			case 0:
				break;
			case 1:
				if($unique_compiler_data[0]['compiler-options'] != null)
				{
					$graph->addTestNote($compiler_options_string . $unique_compiler_data[0]['compiler-options'], $unique_compiler_data[0]['compiler-options']);
				}
				break;
			default:
				$diff = call_user_func_array('array_diff', $unique_compiler_data);

				if(count($diff) == 1)
				{
					$key = array_keys($diff);
					$key = array_pop($key);
				}

				if(isset($key) && isset($diff[$key]))
				{
					$unique_compiler_data = array();
					foreach($json as $identifier => &$data)
					{
						if(isset($data['compiler-options'][$key]))
						{
							$d = explode(' ', $data['compiler-options'][$key]);

							if(!in_array($d, $unique_compiler_data))
							{
								$unique_compiler_data[$identifier] = $d;
							}
						}
					}

					if(empty($unique_compiler_data))
					{
						break;
					}

					if($key == 'compiler-options')
					{
						$intersect = count($unique_compiler_data) == 1 ? reset($unique_compiler_data) : call_user_func_array('array_intersect', $unique_compiler_data);
						$graph->addTestNote($compiler_options_string . implode(' ', $intersect));
					}

					if(count($unique_compiler_data) > 1)
					{
						foreach($json as $identifier => &$data)
						{
							if(isset($data['compiler-options'][$key]))
							{
								$options = explode(' ', $data['compiler-options'][$key]);
								$diff = implode(' ', array_diff($options, $intersect));

								if($diff)
								{
									$graph->addGraphIdentifierNote($identifier, $diff);
								}
							}
						}
					}
				}
				break;
		}

		foreach($json as $identifier => &$data)
		{
			$graph_identifier_note = null;
			if(isset($data['min-result']))
			{
				$graph_identifier_note .= 'MIN: ' . $data['min-result'];
			}
			if(isset($data['max-result']))
			{
				$graph_identifier_note .= ($graph_identifier_note == null ? '' : ' / ') . 'MAX: ' . $data['min-result'];
			}

			if($graph_identifier_note)
			{
				$graph->addGraphIdentifierNote($identifier, $graph_identifier_note);
			}

			if(isset($data['install-footnote']) && $data['install-footnote'] != null)
			{
				$graph->addTestNote($identifier . ': ' . $data['install-footnote']);
			}
		}
	}
	public static function evaluate_redundant_identifier_words($identifiers)
	{
		if(count($identifiers) < 4 || strpos(pts_arrays::first_element($identifiers), ':') !== false)
		{
			// Probably not worth shortening so few result identifiers
			return false;
		}

		// Breakup the an identifier into an array by spaces to be used for comparison
		$common_segments = explode(' ', pts_arrays::first_element($identifiers));
		$common_segments_last = explode(' ', pts_arrays::last_element($identifiers));

		if(!isset($common_segments_last[2]) || !isset($common_segments[2]))
		{
			// If there aren't at least three words in identifier, probably can't be shortened well
			return false;
		}

		foreach(array_reverse($identifiers) as $id)
		{
			$words = explode(' ', $id);

			foreach($words as $i => $word)
			{
				if(isset($common_segments[$i]) && $word != $common_segments[$i] && isset($word[2]) && !pts_strings::is_alnum(substr($word, -1)))
				{
					// IS COMMON WORD
				}
				else
				{
					unset($common_segments[$i]);
				}
			}

			if(count($common_segments) == 0)
			{
				return false;
			}
		}

		return $common_segments;
	}
	public static function generate_overview_object(&$overview_table, $overview_type)
	{
		switch($overview_type)
		{
			case 'GEOMETRIC_MEAN':
				$title = 'Geometric Mean';
				$math_call = array('pts_math', 'geometric_mean');
				break;
			case 'HARMONIC_MEAN':
				$title = 'Harmonic Mean';
				$math_call = array('pts_math', 'harmonic_mean');
				break;
			case 'AGGREGATE_SUM':
				$title = 'Aggregate Sum';
				$math_call = 'array_sum';
				break;
			default:
				return false;

		}
		$result_buffer = new pts_test_result_buffer();

		if($overview_table instanceof pts_result_file)
		{
			list($days_keys1, $days_keys, $shred) = pts_ResultFileTable::result_file_to_result_table($overview_table);

			foreach($shred as $system_key => &$system)
			{
				$to_show = array();

				foreach($system as &$days)
				{
					$days = $days->get_value();
				}

				$to_show[] = pts_math::set_precision(call_user_func($math_call, $system), 2);
				$result_buffer->add_test_result($system_key, implode(',', $to_show), null);
			}
		}
		else
		{
			$days_keys = null;
			foreach($overview_table as $system_key => &$system)
			{
				if($days_keys == null)
				{
					// Rather messy and inappropriate way of getting the days keys
					$days_keys = array_keys($system);
					break;
				}
			}

			foreach($overview_table as $system_key => &$system)
			{
				$to_show = array();

				foreach($system as &$days)
				{
					$to_show[] = call_user_func($math_call, $days);
				}

				$result_buffer->add_test_result($system_key, implode(',', $to_show), null);
			}
		}

		$test_profile = new pts_test_profile(null, null, false);
		$test_profile->set_test_title($title);
		$test_profile->set_result_scale($title);
		$test_profile->set_display_format('BAR_GRAPH');

		$test_result = new pts_test_result($test_profile);
		$test_result->set_used_arguments_description('Analytical Overview');
		$test_result->set_test_result_buffer($result_buffer);

		return $test_result;
	}
	public static function multi_way_identifier_check($identifiers)
	{
		/*
			Samples To Use For Testing:
			1109026-LI-AMDRADEON57
		*/

		if(count($identifiers) < 2)
		{
			return false;
		}

		$systems = array();
		$targets = array();
		$is_multi_way = true;
		$is_multi_way_inverted = false;
		$is_ordered = true;
		$prev_system = null;

		foreach($identifiers as $identifier)
		{
			$identifier_r = explode(':', $identifier);

			if(count($identifier_r) != 2 || (isset($identifier[14]) && $identifier[4] == '-' && $identifier[13] == ':'))
			{
				// the later check will fix 0000-00-00 00:00 as breaking into date
				return false;
			}

			if(false && $is_ordered && $prev_system != null && $prev_system != $identifier_r[0] && isset($systems[$identifier_r[0]]))
			{
				// The results aren't ordered
				$is_ordered = false;
			}

			$prev_system = $identifier_r[0];
			$systems[$identifier_r[0]] = !isset($systems[$identifier_r[0]]) ? 1 : $systems[$identifier_r[0]] + 1;
			$targets[$identifier_r[1]] = !isset($targets[$identifier_r[1]]) ? 1 : $targets[$identifier_r[1]] + 1;
		}

		$is_multi_way_inverted = $is_multi_way && count($targets) > count($systems);

		return $is_multi_way ? array($is_multi_way, $is_multi_way_inverted) : false;
	}
	public static function renderer_compatibility_check($user_agent)
	{
		$user_agent .= ' ';
		$selected_renderer = 'SVG';

		// Yahoo Slurp, msnbot, and googlebot should always be served SVG so no problems there

		if(($p = strpos($user_agent, 'Gecko/')) !== false)
		{
			// Mozilla Gecko-based browser (Firefox, etc)
			$gecko_date = substr($user_agent, ($p + 6));
			$gecko_date = substr($gecko_date, 0, 6);

			// Around Firefox 3.0 era is best
			// Firefox 2.0 mostly works except text might not show...
			// With Firefox 17.0 it's now Gecko/17.0 rather than a date...
			if(substr($gecko_date, 0, 3) == '200' && $gecko_date < 200702)
			{
				$selected_renderer = 'PNG';
			}
		}
		else if(($p = strpos($user_agent, 'AppleWebKit/')) !== false && strpos($user_agent, 'Chrome/') === false)
		{
			// All modern versions of Chrome should work with SVG
			// Safari, Google Chrome, Google Chromium, etc
			// Any version of Chrome should be okay
			$webkit_ver = substr($user_agent, ($p + 12));
			$webkit_ver = substr($webkit_ver, 0, strpos($webkit_ver, ' '));

			// Webkit 532.2 534.6 (WebOS 3.0.2) on WebOS is buggy for SVG
			// iPhone OS is using 533 right now
			if($webkit_ver < 533 || strpos($user_agent, 'hpwOS') !== false)
			{
				$selected_renderer = 'PNG';
			}

			if(($p = strpos($user_agent, 'Android ')) !== false)
			{
				$android_ver = substr($user_agent, ($p + 8), 3);

				// Android browser doesn't support SVG.
				// Google bug report 1376 for Android - http://code.google.com/p/android/issues/detail?id=1376
				// Looks like it might work though in 3.0 Honeycomb
				if($android_ver < 3.0)
				{
					$selected_renderer = 'PNG';
				}
			}
		}
		else if(($p = strpos($user_agent, 'KHTML/')) !== false)
		{
			// KDE Konqueror as of 4.7 is still broken for SVG
			$selected_renderer = 'PNG';
		}
		else if(($p = strpos($user_agent, 'MSIE ')) !== false)
		{
			$ver = substr($user_agent, ($p + 5), 1);

			// Microsoft Internet Explorer 9.0 finally seems to do SVG right
			if($ver < 10 && $ver != 1)
			{
				$selected_renderer = 'PNG';
			}
		}
		else if(strpos($user_agent, 'facebook') !== false)
		{
			// Facebook uses this string for its Like/Share crawler, so serve it a PNG so it can use it as an image
			$selected_renderer = 'PNG';
		}

		return $selected_renderer;
	}
}

?>
