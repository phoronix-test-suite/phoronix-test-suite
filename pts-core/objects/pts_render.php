<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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
					$graph = base64_encode($graph);
					$graph = "<img src=\"data:image/png;base64,$conts\" />";
				}
				else
				{
					header("Content-Type: image/" . strtolower($graph->graph_image->get_renderer()));
				}
				break;
			case 'SVG':
				$graph = $graph->render_graph_finish();

				if($nested)
				{
					// strip out any DOCTYPE and other crud that would be redundant, so start at SVG tag
					$graph = substr($graph, strpos($graph, "<svg"));
				}
				else
				{
					header("Content-type: image/svg+xml");
				}

				//$graph = "<object type=\"image/svg+xml\">" . $svg . "</object>";
				//$graph = "<embed type=\"image/svg+xml\" width=\"" . $graph->graphWidth() . "\" height=\"" . $graph->graphHeight() . "\">" . $svg . "</embed>";
				// or in WebKit / Chrome / Safari right now we need to embed in <img> if wanting to use auto width/height
				break;
			default:
				$graph = $graph->render_graph_finish();
				break;
		}

		return $graph;
	}
	public static function render_graph_process(&$result_object, &$result_file = null, $save_as = false, $extra_attributes = null)
	{
		if($result_file != null && ($result_file->is_multi_way_comparison() || $result_file->is_results_tracker()))
		{
			if($result_file->is_multi_way_comparison() && $result_object->test_profile->get_display_format() == "LINE_GRAPH")
			{
				// Turn a multi-way line graph into an averaged bar graph
				$buffer_items = $result_object->test_result_buffer->get_buffer_items();
				$result_object->test_result_buffer = new pts_test_result_buffer();

				foreach($buffer_items as $buffer_item)
				{
					$values = pts_strings::comma_explode($buffer_item->get_result_value());
					$avg_value = array_sum($values) / count($values);
					$result_object->test_result_buffer->add_test_result($buffer_item->get_result_identifier(), $avg_value, $avg_value);
				}

				$result_object->test_profile->set_display_format("BAR_GRAPH");
			}

			$result_table = false;

			if($result_object->test_profile->get_display_format() != "PIE_CHART")
			{
				pts_render::compact_result_file_test_object($result_object, $result_table, $result_file->is_multi_way_inverted());
			}
		}

		$display_format = $result_object->test_profile->get_display_format();
		static $bar_orientation = null;

		if($bar_orientation == null)
		{
			switch(pts_Graph::$graph_config->getXmlValue(P_GRAPH_BAR_ORIENTATION))
			{
				case "VERTICAL":
					$preferred_bar_graph_type = "pts_VerticalBarGraph";
					break;
				case "HORIZONTAL":
				default:
					$preferred_bar_graph_type = "pts_HorizontalBarGraph";
					break;
			}
		}

		switch($display_format)
		{
			case "LINE_GRAPH":
				$graph_type = "pts_LineGraph";
				break;
			case "BAR_ANALYZE_GRAPH":
			case "BAR_GRAPH":
				$graph_type = $preferred_bar_graph_type;
				break;
			case "PASS_FAIL":
				$graph_type = "pts_PassFailGraph";
				break;
			case "MULTI_PASS_FAIL":
				$graph_type = "pts_MultiPassFailGraph";
				break;
			case "TEST_COUNT_PASS":
				$graph_type = "pts_TestCountPassGraph";
				break;
			case "PIE_CHART":
				$graph_type = "pts_PieChart";
				break;
			case "IMAGE_COMPARISON":
				$graph_type = "pts_ImageComparisonGraph";
				break;
			default:
				if(isset($extra_attributes["graph_render_type"]))
				{
					$requested_graph_type = $extra_attributes["graph_render_type"];
				}
				else if(defined("GRAPH_RENDER_TYPE"))
				{
					$requested_graph_type = GRAPH_RENDER_TYPE;
				}
				else
				{
					$requested_graph_type = null;
				}

				switch($requested_graph_type)
				{
					case "CANDLESTICK":
						$graph_type = "pts_CandleStickGraph";
						break;
					case "LINE_GRAPH":
						$graph_type = "pts_LineGraph";
						break;
					default:
						$graph_type = $preferred_bar_graph_type;
						break;
				}
				break;
		}

		// creation code
		eval("\$graph = new " . $graph_type . "(\$result_object, \$result_file);");


		if(isset($extra_attributes["regression_marker_threshold"]))
		{
			$graph->markResultRegressions($extra_attributes["regression_marker_threshold"]);
		}

		switch($display_format)
		{
			case "LINE_GRAPH":
				if(isset($extra_attributes["no_overview_text"]) && $graph instanceof pts_LineGraph)
				{
					$graph->plot_overview_text = false;
				}
			case "BAR_ANALYZE_GRAPH":
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
	public static function generate_overview_object(&$overview_table, $overview_type)
	{
		$result_buffer = new pts_test_result_buffer();
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

		switch($overview_type)
		{
			case "GEOMETRIC_MEAN":
				$title = "Geometric Mean";
				$math_call = array("pts_math", "geometric_mean");
				break;
			case "HARMONIC_MEAN":
				$title = "Harmonic Mean";
				$math_call = array("pts_math", "harmonic_mean");
				break;
			case "AGGREGATE_SUM":
				$title = "Aggregate Sum";
				$math_call = "array_sum";
				break;
			default:
				return false;

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

		$test_profile = new pts_test_profile(null);
		$test_profile->set_test_title("Results Overview");
		$test_profile->set_result_scale($title . " | " . implode(',', $days_keys));
		$test_profile->set_display_format("LINE_GRAPH");

		$test_result = new pts_test_result();
		$test_result->set_used_arguments_description("Phoromatic Tracker: " . $title);
		$test_result->set_test_result_buffer($result_buffer);

		return $test_result;
	}
	public static function compact_result_file_test_object(&$mto, &$result_table = false, $identifiers_inverted = false)
	{
		// TODO: this may need to be cleaned up, its logic is rather messy
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
			$identifier = pts_strings::trim_explode(": ", $buffer_item->get_result_identifier());

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
				// Check to see if only numeric changes are being made
				$date = pts_strings::remove_from_string($date, pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH | pts_strings::CHAR_DECIMAL);

				if($prev_date != null && $date != $prev_date)
				{
					$is_tracking = false;
				}

				$prev_date = $date;
			}
		}

		foreach(array_keys($days) as $day_key)
		{
			$days[$day_key] = $systems;
		}

		$raw_days = $days;

		foreach($mto->test_result_buffer->get_buffer_items() as $buffer_item)
		{
			$identifier = pts_strings::trim_explode(": ", $buffer_item->get_result_identifier());

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

		$mto->test_profile->set_result_scale($mto->test_profile->get_result_scale() . ' | ' . implode(',', array_keys($days)));
		$mto->test_profile->set_display_format((count($days) < 5 || $is_tracking == false ? "BAR_ANALYZE_GRAPH" : "LINE_GRAPH"));
		$mto->test_result_buffer = new pts_test_result_buffer();
		$day_keys = array_keys($days);

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
	public static function multi_way_identifier_check($identifiers, &$system_hardware = null)
	{
		$systems = array();
		$targets = array();
		$is_multi_way = true;
		$is_multi_way_inverted = false;
		$prev_system = null;

		foreach($identifiers as $identifier)
		{
			$identifier_r = pts_strings::trim_explode(': ', $identifier);

			if(count($identifier_r) != 2)
			{
				$is_multi_way = false;
				break;
			}

			if($prev_system != null && $prev_system != $identifier_r[0] && isset($systems[$identifier_r[0]]))
			{
				// The results aren't ordered
				$is_multi_way = false;
				break;
			}

			$prev_system = $identifier_r[0];
			$systems[$identifier_r[0]] = !isset($systems[$identifier_r[0]]) ? 1 : $systems[$identifier_r[0]] + 1;
			$targets[$identifier_r[1]] = !isset($targets[$identifier_r[1]]) ? 1 : $targets[$identifier_r[1]] + 1;	
		}

		$is_multi_way_inverted = count($targets) > count($systems);

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
