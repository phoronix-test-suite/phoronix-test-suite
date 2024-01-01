<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2024, Phoronix Media
	Copyright (C) 2008 - 2024, Michael Larabel

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
	public static function attribute_processing_on_result_object(&$result_object, &$result_file = null, $extra_attributes = null)
	{
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
			$result_object->normalize_buffer_values($normalize_against, $extra_attributes);
		}
		if(isset($extra_attributes['sort_result_buffer_values']))
		{
			$result_object->sort_results_by_performance();
		}
		if(isset($extra_attributes['reverse_result_buffer']))
		{
			$result_object->test_result_buffer->buffer_values_reverse();
		}
	}
	public static function render_graph_process(&$result_object, &$result_file = null, $save_as = false, $extra_attributes = null)
	{
		// NOTICE: $save_as doesn't appear used anymore
		self::attribute_processing_on_result_object($result_object, $result_file, $extra_attributes);

		$has_a_result = false;
		foreach($result_object->test_result_buffer->buffer_items as &$buffer_item)
		{
			// Check to make sure at least one result is not null/empty so there is something to render for the graph
			// i.e. make sure not all of the results failed to run for this result object
			if($buffer_item->get_result_value() != null)
			{
				$has_a_result = true;
				break;
			}
		}
		if(!$has_a_result)
		{
			return false;
		}

		$horizontal_bars = pts_graph_core::get_graph_config('style', 'bar_graphs_horizontal') && !isset($extra_attributes['vertical_bars']);

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
				if((isset($extra_attributes['compact_to_scalar']) || (false && $result_file->is_multi_way_comparison($result_identifiers, $extra_attributes))) && in_array($result_object->test_profile->get_display_format(), array('LINE_GRAPH', 'FILLED_LINE_GRAPH')) && pts_graph_core::get_graph_config('style', 'allow_box_plots'))
				{
					// Convert multi-way line graph into horizontal box plot
					$result_object->test_profile->set_display_format('HORIZONTAL_BOX_PLOT');
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

			if(in_array($result_object->test_profile->get_display_format(), array('LINE_GRAPH')) && !isset($extra_attributes['force_tracking_line_graph']))
			{
					// Check to see for line graphs if every result is an array of the same result (i.e. a flat line for every result).
					// If all the results are just flat lines, you might as well convert it to a bar graph
					$buffer_items = $result_object->test_result_buffer->get_buffer_items();
					$all_values_are_flat = false;
					$big_data_set = 0;
					$flat_values = array();

					foreach($buffer_items as $i => $buffer_item)
					{
						$values_in_buffer = !is_array($buffer_item->get_result_value()) ? explode(',', $buffer_item->get_result_value()) : $buffer_item->get_result_value();
						$unique_in_buffer = array_unique($values_in_buffer);
						$all_values_are_flat = count($unique_in_buffer) == 1;

						if(isset($values_in_buffer[1200]))
						{
							// if more than 1200 points to plot, likely will be messy so condense it
							$big_data_set++;
						}

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
					else if($big_data_set > 0 && pts_graph_core::get_graph_config('style', 'allow_box_plots') && !isset($extra_attributes['no_box_plots']))
					{
						$result_object->test_profile->set_display_format('HORIZONTAL_BOX_PLOT');
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
				if(pts_graph_core::get_graph_config('style', 'allow_box_plots'))
				{
					$graph = new pts_graph_box_plot($result_object, $result_file, $extra_attributes);
					break;
				}
			case 'BAR_ANALYZE_GRAPH':
			case 'BAR_GRAPH':
				if($horizontal_bars)
				{
					$graph = new pts_graph_horizontal_bars($result_object, $result_file, $extra_attributes);
				}
				else
				{
					$graph = new pts_graph_vertical_bars($result_object, $result_file, $extra_attributes);
				}
				break;
			case 'VERTICAL_BAR_GRAPH':
				$graph = new pts_graph_vertical_bars($result_object, $result_file, $extra_attributes);
				break;
			case 'PASS_FAIL':
			case 'MULTI_PASS_FAIL':
				$graph = new pts_graph_passfail($result_object, $result_file, $extra_attributes);
				break;
			case 'PIE_CHART':
				$graph = new pts_graph_pie_chart($result_object, $result_file, $extra_attributes);
				break;
			case 'IMAGE_COMPARISON':
				$graph = new pts_graph_iqc($result_object, $result_file, $extra_attributes);
				break;
			case 'SCATTER_PLOT':
				$graph = new pts_graph_scatter_plot($result_object, $result_file, $extra_attributes);
				break;
			default:
				if($horizontal_bars)
				{
					$graph = new pts_graph_horizontal_bars($result_object, $result_file, $extra_attributes);
				}
				else
				{
					$graph = new pts_graph_vertical_bars($result_object, $result_file, $extra_attributes);
				}
				break;
		}

		self::report_test_notes_to_graph($graph, $result_object);

		return $graph;
	}
	public static function identifier_to_brand_color($identifier, $fallback_color = null)
	{
		static $cache;

		if(empty($identifier))
		{
			return $fallback_color;
		}
		if(isset($cache[$identifier]))
		{
			return $cache[$identifier] != null ? $cache[$identifier] : $fallback_color;
		}

		// See if the result identifier matches something to be color-coded better
		$i = strtolower($identifier) . ' ';
		if(strpos($i, 'geforce') !== false || strpos($i, 'nvidia') !== false || strpos($i, 'quadro') !== false || strpos($i, 'rtx ') !== false || strpos($i, 'gtx ') !== false)
		{
			$paint_color = '#77b900';
		}
		else if(strpos($i, 'radeon') !== false || strpos($i, 'amd ') !== false || stripos($i, 'EPYC') !== false || strpos($i, 'opteron ') !== false || strpos($i, 'fx-') !== false || strpos($i, 'firepro ') !== false || strpos($i, 'ryzen ') !== false || strpos($i, 'threadripper ') !== false || strpos($i, 'a10-') !== false || strpos($i, 'athlon ') !== false || (strpos($i, 'r9 ') !== false && strpos($i, 'power9 ') === false) || strpos($i, 'r7 ') !== false || strpos($i, 'r9 ') !== false || strpos($i, 'hd 7') !== false || (strpos($i, 'rx ') !== false && strpos($i, 'thunderx ') === false))
		{
			$paint_color = '#f1052d';
		}
		else if(strpos($i, 'intel ') !== false || strpos($i, 'xeon ') !== false || strpos($i, 'core i') !== false || strpos($i, 'core ultra') !== false || strpos($i, 'pentium') !== false || strpos($i, 'celeron') !== false)
		{
			$paint_color = '#0b5997';
		}
		else if(strpos($i, 'bsd') !== false)
		{
			$paint_color = '#850000';
		}
		else if(stripos($i, 'windows ') !== false || stripos($i, 'Microsoft') !== false)
		{
			$paint_color = '#373277';
		}
		else if(stripos($i, 'ec2 ') !== false || stripos($i, 'Amazon') !== false)
		{
			$paint_color = '#ff9900';
		}
		else if(stripos($i, 'google') !== false)
		{
			$paint_color = '#4885ed';
		}
		else if(stripos($i, 'arm ') !== false)
		{
			$paint_color = '#f6d452';
		}
		else
		{
			$paint_color = $fallback_color;
		}

		$cache[$identifier] = $paint_color != $fallback_color ? $paint_color : null;

		return $paint_color;
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

					$intersect = array();
					if($key == 'compiler-options')
					{
						$intersect = count($unique_compiler_data) == 1 ? reset($unique_compiler_data) : call_user_func_array('array_intersect', array_values($unique_compiler_data));
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

		// Deduplicate the footnote if it's all the same across all tests
		$same_footnote = null;
		foreach($json as $identifier => &$data)
		{
			if(isset($data['install-footnote']) && $data['install-footnote'] != null)
			{
				if($same_footnote === null)
				{
					$same_footnote = $data['install-footnote'];
					continue;
				}

				if($data['install-footnote'] != $same_footnote)
				{
					$same_footnote = null;
					break;
				}
			}
		}

		if($same_footnote)
		{
			// Show the same footnote once rather than duplicated to all tests
			$graph->addTestNote($same_footnote);
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
				$graph_identifier_note .= ($graph_identifier_note == null ? '' : ' / ') . 'MAX: ' . $data['max-result'];
			}

			if($graph_identifier_note)
			{
				$graph->addGraphIdentifierNote($identifier, $graph_identifier_note);
			}

			if($same_footnote == null && isset($data['install-footnote']) && $data['install-footnote'] != null)
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
		// 2021: all relatively recent browsers of thep ast decade should be fine...
		/*
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
		*/

		return $selected_renderer;
	}
}

?>
