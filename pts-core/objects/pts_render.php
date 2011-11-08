<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel

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
	public static $last_graph_object = null;

	public static function render_graph(&$result_object, &$result_file = null, $save_as = false, $extra_attributes = null)
	{
		$graph = self::render_graph_process($result_object, $result_file, $save_as, $extra_attributes);
		return $graph->renderGraph();
	}
	public static function render_graph_inline_embed(&$object, &$result_file = null, $extra_attributes = null, $nested = true)
	{
		if($object instanceof pts_test_result)
		{
			$graph = self::render_graph_process($object, $result_file, false, $extra_attributes);
		}
		else if($object instanceof pts_Graph)
		{
			$graph = $object;
		}
		else
		{
			return null;
		}

		$graph->render_graph_start();
		switch($graph->graph_image->get_renderer())
		{
			case 'PNG':
			case 'JPG':
				$save_as = tempnam('/tmp', 'pts_gd_render');
				$graph->saveGraphToFile($save_as);
				$graph->render_graph_finish();
				$graph = file_get_contents($save_as);
				unlink($save_as);

				if($nested)
				{
					$graph = '<img src="data:image/png;base64,' . base64_encode($graph) . '" />';
				}
				else
				{
					header('Content-Type: image/' . strtolower($graph->graph_image->get_renderer()));
				}
				break;
			case 'SVG':
				$svg_graph = $graph->render_graph_finish();

				if($nested)
				{
					// strip out any DOCTYPE and other crud that would be redundant, so start at SVG tag
					$svg_graph = substr($svg_graph, strpos($svg_graph, '<svg'));
				}
				else
				{
					header('Content-type: image/svg+xml');
				}

				$graph = $svg_graph;
				break;
			default:
				$graph = $graph->render_graph_finish();
				break;
		}

		return $graph;
	}
	public static function multi_way_compact(&$result_file, &$result_object, $extra_attributes = null)
	{
		if($result_file == null)
		{
			return;
		}

		if(!isset($extra_attributes['compact_to_scalar']) && $result_object->test_profile->get_display_format() == 'LINE_GRAPH' && $result_file->get_system_count() > 8)
		{
			// If there's too many lines being plotted on line graph, likely to look messy, so convert to scalar automatically
			$extra_attributes['compact_to_scalar'] = true;
		}

		if($result_file->is_multi_way_comparison() || isset($extra_attributes['compact_to_scalar']) || $result_file->is_results_tracker())
		{
			if((isset($extra_attributes['compact_to_scalar']) || (false && $result_file->is_multi_way_comparison())) && in_array($result_object->test_profile->get_display_format(), array('LINE_GRAPH', 'FILLED_LINE_GRAPH')))
			{
				// Convert multi-way line graph into horizontal box plot
				if(false)
				{
					$result_object->test_profile->set_display_format('HORIZONTAL_BOX_PLOT');
				}
				else
				{
					// Turn a multi-way line graph into an averaged bar graph

					$buffer_items = $result_object->test_result_buffer->get_buffer_items();
					$result_object->test_result_buffer = new pts_test_result_buffer();

					foreach($buffer_items as $buffer_item)
					{
						$values = pts_strings::comma_explode($buffer_item->get_result_value());
						$avg_value = pts_math::set_precision(array_sum($values) / count($values), 2);
						$result_object->test_result_buffer->add_test_result($buffer_item->get_result_identifier(), $avg_value);
					}

					$result_object->test_profile->set_display_format('BAR_GRAPH');
				}
			}

			if($result_object->test_profile->get_display_format() != 'PIE_CHART')
			{
				$result_table = false;
				pts_render::compact_result_file_test_object($result_object, $result_table, $result_file->is_multi_way_inverted(), $extra_attributes);
			}
		}
	}
	public static function render_graph_process(&$result_object, &$result_file = null, $save_as = false, $extra_attributes = null)
	{
		if(isset($extra_attributes['reverse_result_buffer']))
		{
			$result_object->test_result_buffer->buffer_values_reverse();
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

		if($result_file != null)
		{
			// Cache the redundant words on identifiers so it's not re-computed on every graph
			static $redundant_word_cache;

			if(!isset($redundant_word_cache[$result_file->get_title()]))
			{
				$redundant_word_cache[$result_file->get_title()] = pts_render::evaluate_redundant_identifier_words($result_file->get_system_identifiers());
			}

			if($redundant_word_cache[$result_file->get_title()])
			{
				$result_object->test_result_buffer->auto_shorten_buffer_identifiers($redundant_word_cache[$result_file->get_title()]);
			}
		}
		self::multi_way_compact($result_file, $result_object, $extra_attributes);

		$display_format = $result_object->test_profile->get_display_format();
		static $bar_orientation = null;

		if($bar_orientation == null)
		{
			$bar_orientation = pts_Graph::$graph_config->getXmlValue('PhoronixTestSuite/Graphs/General/BarOrientation');
		}

		switch($display_format)
		{
			case 'LINE_GRAPH':
				if(false && $result_object->test_result_buffer->get_count() > 5)
				{
					// If there's too many lines close to each other, it's likely to look cluttered so turn it into horizontal range bar / box chart graph
					$display_format = 'HORIZONTAL_BOX_PLOT';
					$graph = new pts_HorizontalBoxPlotGraph($result_object, $result_file);
				}
				else
				{
					$graph = new pts_LineGraph($result_object, $result_file);
				}
				break;
			case 'HORIZONTAL_BOX_PLOT':
				$graph = new pts_HorizontalBoxPlotGraph($result_object, $result_file);
				break;
			case 'BAR_ANALYZE_GRAPH':
			case 'BAR_GRAPH':
				if($bar_orientation == 'VERTICAL')
				{
					$graph = new pts_VerticalBarGraph($result_object, $result_file);
				}
				else
				{
					$graph = new pts_HorizontalBarGraph($result_object, $result_file);
				}
				break;
			case 'PASS_FAIL':
				$graph = new pts_PassFailGraph($result_object, $result_file);
				break;
			case 'MULTI_PASS_FAIL':
				$graph = new pts_MultiPassFailGraph($result_object, $result_file);
				break;
			case 'TEST_COUNT_PASS':
				$graph = new pts_TestCountPassGraph($result_object, $result_file);
				break;
			case 'PIE_CHART':
				$graph = new pts_PieChart($result_object, $result_file);
				break;
			case 'IMAGE_COMPARISON':
				$graph = new pts_ImageComparisonGraph($result_object, $result_file);
				break;
			case 'FILLED_LINE_GRAPH':
				$graph = new pts_FilledLineGraph($result_object, $result_file);
				break;
			case 'SCATTER_PLOT':
				$graph = new pts_ScatterPlot($result_object, $result_file);
				break;
			default:
				if(isset($extra_attributes['graph_render_type']))
				{
					$requested_graph_type = $extra_attributes['graph_render_type'];
				}
				else if(defined('GRAPH_RENDER_TYPE'))
				{
					$requested_graph_type = GRAPH_RENDER_TYPE;
				}
				else
				{
					$requested_graph_type = null;
				}

				switch($requested_graph_type)
				{
					case 'CANDLESTICK':
						$graph = new pts_CandleStickGraph($result_object, $result_file);
						break;
					case 'LINE_GRAPH':
						$graph = new pts_LineGraph($result_object, $result_file);
						break;
					case 'FILLED_LINE_GRAPH':
						$graph = new pts_FilledLineGraph($result_object, $result_file);
						break;
					default:
						if($bar_orientation == 'VERTICAL')
						{
							$graph = new pts_VerticalBarGraph($result_object, $result_file);
						}
						else
						{
							$graph = new pts_HorizontalBarGraph($result_object, $result_file);
						}
						break;
				}
				break;
		}

		if(isset($extra_attributes['regression_marker_threshold']))
		{
			$graph->markResultRegressions($extra_attributes['regression_marker_threshold']);
		}
		if(isset($extra_attributes['set_alternate_view']))
		{
			$graph->setAlternateView($extra_attributes['set_alternate_view']);
		}
		if(isset($extra_attributes['sort_result_buffer_values']))
		{
			$result_object->test_result_buffer->buffer_values_sort();

			if($result_object->test_profile->get_result_proportion() == 'HIB')
			{
				$result_object->test_result_buffer->buffer_values_reverse();
			}
		}
		if(isset($extra_attributes['highlight_graph_values']))
		{
			$graph->highlight_values($extra_attributes['highlight_graph_values']);
		}
		else if(PTS_IS_CLIENT && pts_client::read_env('GRAPH_HIGHLIGHT') != false)
		{
			$graph->highlight_values(pts_strings::comma_explode(pts_client::read_env('GRAPH_HIGHLIGHT')));
		}

		switch($display_format)
		{
			case 'LINE_GRAPH':
				if(isset($extra_attributes['no_overview_text']) && $graph instanceof pts_LineGraph)
				{
					$graph->plot_overview_text = false;
				}
			case 'FILLED_LINE_GRAPH':
			case 'BAR_ANALYZE_GRAPH':
			case 'SCATTER_PLOT':
				//$graph->hideGraphIdentifiers();
				foreach($result_object->test_result_buffer->get_buffer_items() as $buffer_item)
				{
					$graph->loadGraphValues(pts_strings::comma_explode($buffer_item->get_result_value()), $buffer_item->get_result_identifier());
					$graph->loadGraphRawValues(pts_strings::comma_explode($buffer_item->get_result_raw()));
				}

				$scale_special = $result_object->test_profile->get_result_scale_offset();
				if(!empty($scale_special) && count(($ss = pts_strings::comma_explode($scale_special))) > 0)
				{
					$graph->loadGraphIdentifiers($ss);
				}
				break;
			case 'HORIZONTAL_BOX_PLOT':
				// TODO: should be able to load pts_test_result_buffer_item objects more cleanly into pts_Graph
				$identifiers = array();
				$values = array();

				foreach($result_object->test_result_buffer->get_buffer_items() as $buffer_item)
				{
					array_push($identifiers, $buffer_item->get_result_identifier());
					array_push($values, pts_strings::comma_explode($buffer_item->get_result_value()));
				}

				$graph->loadGraphIdentifiers($identifiers);
				$graph->loadGraphValues($values);
				break;
			default:
				// TODO: should be able to load pts_test_result_buffer_item objects more cleanly into pts_Graph
				$identifiers = array();
				$values = array();
				$raw_values = array();

				foreach($result_object->test_result_buffer->get_buffer_items() as $buffer_item)
				{
					array_push($identifiers, $buffer_item->get_result_identifier());
					array_push($values, $buffer_item->get_result_value());
					array_push($raw_values, $buffer_item->get_result_raw());
				}

				$graph->loadGraphIdentifiers($identifiers);
				$graph->loadGraphValues($values);
				$graph->loadGraphRawValues($raw_values);
				break;
		}

		if($save_as)
		{
			$graph->saveGraphToFile($save_as);
		}

		if(PTS_IS_CLIENT)
		{
			self::$last_graph_object = $graph;
		}

		return $graph;
	}
	public static function evaluate_redundant_identifier_words($identifiers)
	{
		if(count($identifiers) < 6)
		{
			// Probably not worth shortening so few result identifiers
			return false;
		}

		// Breakup the an identifier into an array by spaces to be used for comparison
		$common_segments = explode(' ', pts_arrays::last_element($identifiers));

		if(!isset($common_segments[2]))
		{
			// If there aren't at least three words in identifier, probably can't be shortened well
			return false;
		}

		foreach($identifiers as &$identifier)
		{
			$this_identifier = explode(' ', $identifier);

			foreach($common_segments as $pos => $word)
			{
				if(!isset($this_identifier[$pos]) || $this_identifier[$pos] != $word)
				{
					// The word isn't the same
					unset($common_segments[$pos]);
				}
			}

			if(count($common_segments) == 0)
			{
				// There isn't any common words to each identifier in result set
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

				array_push($to_show, pts_math::set_precision(call_user_func($math_call, $system), 2));
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
					// TODO: Rather messy and inappropriate way of getting the days keys
					$days_keys = array_keys($system);
					break;
				}
			}

			foreach($overview_table as $system_key => &$system)
			{
				$to_show = array();

				foreach($system as &$days)
				{
					array_push($to_show, call_user_func($math_call, $days));
				}

				$result_buffer->add_test_result($system_key, implode(',', $to_show), null);
			}
		}

		$test_profile = new pts_test_profile(null);
		$test_profile->set_test_title($title);
		$test_profile->set_result_scale($title);
		$test_profile->set_display_format('BAR_GRAPH');

		$test_result = new pts_test_result($test_profile);
		$test_result->set_used_arguments_description('Analytical Overview');
		$test_result->set_test_result_buffer($result_buffer);

		return $test_result;
	}
	public static function compact_result_file_test_object(&$mto, &$result_table = false, $identifiers_inverted = false, $extra_attributes = null)
	{
		// TODO: this may need to be cleaned up, its logic is rather messy
		$condense_multi_way = isset($extra_attributes['condense_multi_way']);
		if(count($mto->test_profile->get_result_scale_offset()) > 0)
		{
			// It's already doing something
			return;
		}

		$scale_special = array();
		$days = array();
		$systems = array();
		$prev_date = null;
		$is_tracking = true;
		$sha1_short_count = 0;

		if($identifiers_inverted)
		{
			$system_index = 0;
			$date_index = 1;
		}
		else
		{
			$system_index = 1;
			$date_index = 0;
		}

		foreach($mto->test_result_buffer->get_buffer_items() as $buffer_item)
		{
			$identifier = pts_strings::trim_explode(':', $buffer_item->get_result_identifier());

			switch(count($identifier))
			{
				case 2:
					$system = $identifier[$system_index];
					$date = $identifier[$date_index];
					break;
				case 1:
					$system = 0;
					$date = $identifier[0];
					break;
				default:
					return;
					break;
			}

			if(!isset($systems[$system]))
			{
				$systems[$system] = 0;
			}
			if(!isset($days[$date]))
			{
				$days[$date] = null;
			}

			if($is_tracking)
			{
				// First do a dirty SHA1 hash check
				if(strlen($date) != 40 || strpos($date, ' ') !== false)
				{
					if(($x = strpos($date, ' + ')) !== false)
					{
						$date = substr($date, 0, $x);
					}

					// Check to see if only numeric changes are being made
					$sha1_short_hash_ending = isset($date[7]) && pts_strings::string_only_contains(substr($date, -8), pts_strings::CHAR_NUMERIC | pts_strings::CHAR_LETTER);
					$date = str_replace('s', null, pts_strings::remove_from_string($date, pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH | pts_strings::CHAR_DECIMAL));

					if($sha1_short_hash_ending)
					{
						$sha1_short_count++;
					}

					if($prev_date != null && $date != $prev_date && $sha1_short_hash_ending == false && $sha1_short_count < 2)
					{
						$is_tracking = false;
					}

					$prev_date = $date;
				}
			}
		}

		foreach(array_keys($days) as $day_key)
		{
			$days[$day_key] = $systems;
		}

		$raw_days = $days;

		foreach($mto->test_result_buffer->get_buffer_items() as $buffer_item)
		{
			$identifier = pts_strings::trim_explode(':', $buffer_item->get_result_identifier());

			switch(count($identifier))
			{
				case 2:
					$system = $identifier[$system_index];
					$date = $identifier[$date_index];
					break;
				case 1:
					$system = 0;
					$date = $identifier[0];
					break;
				default:
					return;
					break;
			}

			$days[$date][$system] = $buffer_item->get_result_value();
			$raw_days[$date][$system] = $buffer_item->get_result_raw();

			if(!is_numeric($days[$date][$system]))
			{
				return;
			}
		}

		$mto->test_result_buffer = new pts_test_result_buffer();
		$day_keys = array_keys($days);

		if($condense_multi_way)
		{
			$mto->set_used_arguments_description($mto->get_arguments_description() . ' | Composite Of: ' . implode(' - ', array_keys($days)));
			foreach(array_keys($systems) as $system_key)
			{
				$sum = 0;
				$count = 0;

				foreach($day_keys as $day_key)
				{
					$sum += $days[$day_key][$system_key];
					$count++;
				}

				$mto->test_result_buffer->add_test_result($system_key, ($sum / $count));
			}
		}
		else
		{
			$mto->test_profile->set_result_scale($mto->test_profile->get_result_scale() . ' | ' . implode(',', array_keys($days)));

			switch($mto->test_profile->get_display_format())
			{
				//case 'HORIZONTAL_BOX_PLOT':
				//	$mto->test_profile->set_display_format('HORIZONTAL_BOX_PLOT_MULTI');
				//	break;
				case 'SCATTER_PLOT';
					break;
				default:
					$line_graph_type = isset($extra_attributes['filled_line_graph']) ? 'FILLED_LINE_GRAPH' : 'LINE_GRAPH';
					$mto->test_profile->set_display_format((count($days) < 5 || ($is_tracking == false && !isset($extra_attributes['force_line_graph_compact'])) ? 'BAR_ANALYZE_GRAPH' : $line_graph_type));
			}

			foreach(array_keys($systems) as $system_key)
			{
				$results = array();
				$raw_results = array();

				foreach($day_keys as $day_key)
				{
					array_push($results, $days[$day_key][$system_key]);
					array_push($raw_results, $raw_days[$day_key][$system_key]);
				}

				$mto->test_result_buffer->add_test_result($system_key, implode(',', $results), implode(',', $raw_results));
			}
		}

		if($result_table !== false)
		{
			foreach(array_keys($systems) as $system_key)
			{
				foreach($day_keys as $day_key)
				{
					if(!isset($result_table[$system_key][$day_key]))
					{
						$result_table[$system_key][$day_key] = array();
					}

					array_push($result_table[$system_key][$day_key], $days[$day_key][$system_key], $raw_days[$day_key][$system_key]);
				}
			}
		}
	}
	public static function multi_way_identifier_check($identifiers, &$system_hardware = null, &$result_file = null)
	{
		/*
			Samples To Use For Testing:
			1109026-LI-AMDRADEON57
		*/

		$systems = array();
		$targets = array();
		$is_multi_way = true;
		$is_multi_way_inverted = false;
		$is_ordered = true;
		$prev_system = null;

		foreach($identifiers as $identifier)
		{
			$identifier_r = pts_strings::trim_explode(':', $identifier);

			if(count($identifier_r) != 2 || (isset($identifier[14]) && $identifier[4] == '-' && $identifier[13] == ':'))
			{
				// the later check will fix 0000-00-00 00:00 as breaking into date
				return false;
			}

			if(false && $is_ordered && $prev_system != null && $prev_system != $identifier_r[0] && isset($systems[$identifier_r[0]]))
			{
				// The results aren't ordered
				$is_ordered = false;

				if($result_file == null)
				{
					return false;
				}
			}

			$prev_system = $identifier_r[0];
			$systems[$identifier_r[0]] = !isset($systems[$identifier_r[0]]) ? 1 : $systems[$identifier_r[0]] + 1;
			$targets[$identifier_r[1]] = !isset($targets[$identifier_r[1]]) ? 1 : $targets[$identifier_r[1]] + 1;	
		}

		if(false && $is_ordered == false && $is_multi_way)
		{
			// TODO: get the reordering code to work
			if($result_file instanceof pts_result_file)
			{
				// Reorder the result file
				$to_order = array();
				sort($identifiers);
				foreach($identifiers as $identifier)
				{
					array_push($to_order, new pts_result_merge_select($result_file, $identifier));
				}

				$ordered_xml = pts_merge::merge_test_results_array($to_order);
				$result_file = new pts_result_file($ordered_xml);
				$is_multi_way = true;
			}
			else
			{
				$is_multi_way = false;
			}
		}

		$is_multi_way_inverted = $is_multi_way && count($targets) > count($systems);

		/*
		if($is_multi_way)
		{
			if(count($systems) < 3 && count($systems) != count($targets))
			{
				$is_multi_way = false;
			}
		}
		*/

		// TODO XXX: for now temporarily disable inverted multi-way check to decide how to rework it appropriately
		/*
		if($is_multi_way)
		{
			$targets_count = count($targets);
			$systems_count = count($systems);

			if($targets_count > $systems_count)
			{
				$is_multi_way_inverted = true;
			}
			else if(is_array($system_hardware))
			{
				$hardware = array_unique($system_hardware);
				//$software = array_unique($system_software);

				if($targets_count != $systems_count && count($hardware) == $systems_count)
				{
					$is_multi_way_inverted = true;
				}
				else if(count($hardware) == ($targets_count * $systems_count))
				{
					$is_multi_way_inverted = true;
				}
			}
		}
		*/

		// TODO: figure out what else is needed to reasonably determine if the result file is a multi-way comparison

		return $is_multi_way ? array($is_multi_way, $is_multi_way_inverted) : false;
	}
}

?>
