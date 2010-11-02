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
	private static $previous_graph_object = null;

	public static function render_graph(&$result_object, &$result_file = null, $save_as = false, $extra_attributes = null)
	{
		if($result_file != null && ($result_file->is_multi_way_comparison() || $result_file->is_results_tracker()))
		{
			if($result_file->is_multi_way_comparison() && $result_object->test_profile->get_result_format() == "LINE_GRAPH")
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

				$result_object->test_profile->set_result_format("BAR_GRAPH");
			}

			$result_table = false;

			if($result_object->test_profile->get_result_format() != "PIE_CHART")
			{
				pts_render::compact_result_file_test_object($result_object, $result_table, $result_file->is_multi_way_inverted());
			}
		}

		$result_format = $result_object->test_profile->get_result_format();
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

		switch($result_format)
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

		switch($result_format)
		{
			case "LINE_GRAPH":
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

		if(PTS_MODE == "CLIENT")
		{
			self::$previous_graph_object = $graph;
		}

		return $graph->renderGraph();
	}
	public static function generate_result_file_graphs($test_results_identifier, $save_to_dir = false)
	{
		if($save_to_dir)
		{
			if(pts_file_io::mkdir($save_to_dir . "/result-graphs", 0777, true) == false)
			{
				// Directory must exist, so remove any old graph files first
				foreach(pts_file_io::glob($save_to_dir . "/result-graphs/*") as $old_file)
				{
					unlink($old_file);
				}
			}
		}

		$result_file = new pts_result_file($test_results_identifier);

		$generated_graphs = array();
		$generated_graph_tables = false;

		// Render overview chart
		if($save_to_dir)
		{
			$chart = new pts_Table($result_file);
			$chart->renderChart($save_to_dir . "/result-graphs/overview.BILDE_EXTENSION");
		}

		foreach($result_file->get_result_objects() as $key => $result_object)
		{
			$save_to = $save_to_dir;

			if($save_to_dir && is_dir($save_to_dir))
			{
				$save_to .= "/result-graphs/" . ($key + 1) . ".BILDE_EXTENSION";

				if(PTS_MODE == "CLIENT")
				{
					if($result_file->is_multi_way_comparison() || pts_client::read_env("GRAPH_GROUP_SIMILAR"))
					{
						$table_keys = array();
						$titles = $result_file->get_test_titles();

						foreach($titles as $this_title_index => $this_title)
						{
							if($this_title == $titles[$key])
							{
								array_push($table_keys, $this_title_index);
							}
						}
					}
					else
					{
						$table_keys = $key;
					}

					$chart = new pts_Table($result_file, null, $table_keys);
					$chart->renderChart($save_to_dir . "/result-graphs/" . ($key + 1) . "_table.BILDE_EXTENSION");
					$generated_graph_tables = true;
				}
			}

			$graph = pts_render::render_graph($result_object, $result_file, $save_to);
			array_push($generated_graphs, $graph);
		}

		// Generate mini / overview graphs
		if($save_to_dir)
		{
			$graph = new pts_OverviewGraph($result_file);

			if($graph->doSkipGraph() == false)
			{
				$graph->saveGraphToFile($save_to_dir . "/result-graphs/visualize.BILDE_EXTENSION");
				$graph->renderGraph();

				// Check to see if skip_graph was realized during the rendering process
				if($graph->doSkipGraph() == true)
				{
					pts_file_io::unlink($save_to_dir . "/result-graphs/visualize.svg");
				}
			}
			unset($graph);
		}

		// Save XSL
		if(count($generated_graphs) > 0 && $save_to_dir)
		{
			file_put_contents($save_to_dir . "/pts-results-viewer.xsl", pts_render::xsl_results_viewer_graph_template($generated_graph_tables));
		}

		return $generated_graphs;
	}
	public static function previous_graph_object()
	{
		return self::$previous_graph_object;
	}
	public static function xsl_results_viewer_graph_template($matching_graph_tables = false)
	{
		$graph_object = pts_render::previous_graph_object();
		$width = $graph_object->graphWidth();
		$height = $graph_object->graphHeight();

		if($graph_object->getRenderer() == "SVG")
		{
			// Hackish way to try to get all browsers to show the entire SVG graph when the graphs may be different size, etc
			$height += 50;
			$width = 600 > $width ? 600 : $width;
			$height = 400 > $height ? 400 : $height;

			// TODO XXX: see if auto works in all browsers
			$width = "auto";
			$height = "auto";
		}

		$raw_xsl = file_get_contents(RESULTS_VIEWER_DIR . "pts-results-viewer.xsl");
		$graph_string = $graph_object->htmlEmbedCode("result-graphs/<xsl:number value=\"position()\" />.BILDE_EXTENSION", $width, $height);

		$raw_xsl = str_replace("<!-- GRAPH TAG -->", $graph_string, $raw_xsl);

		if($matching_graph_tables)
		{
			$bilde_svg = new bilde_svg_renderer(1, 1);
			$table_string = $bilde_svg->html_embed_code("result-graphs/<xsl:number value=\"position()\" />_table.BILDE_EXTENSION", array("width" => "auto", "height" => "auto"), true);
			$raw_xsl = str_replace("<!-- GRAPH TABLE TAG -->", $table_string, $raw_xsl);
		}

		return $raw_xsl;
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
		$test_profile->set_result_format("LINE_GRAPH");

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
			$system_index = 1;
			$date_index = 0;
		}
		else
		{
			$system_index = 0;
			$date_index = 1;
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
		$mto->test_profile->set_result_format((count($days) < 5 || $is_tracking == false ? "BAR_ANALYZE_GRAPH" : "LINE_GRAPH"));
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
	public static function list_regressions_linear(&$result_file, $threshold = 0.05, $show_only_active_regressions = true)
	{
		$regressions = array();

		foreach($result_file->get_result_objects() as $test_index => $result_object)
		{
			$prev_buffer_item = null;
			$this_test_regressions = array();

			foreach($result_object->test_result_buffer->get_buffer_items() as $buffer_item)
			{
				if(!is_numeric($buffer_item->get_result_value()))
				{
					break;
				}

				if($prev_buffer_item != null && abs(1 - ($buffer_item->get_result_value() / $prev_buffer_item->get_result_value())) > $threshold)
				{
					if(defined("PHOROMATIC_TRACKER"))
					{
						$explode_r = explode(': ', $buffer_item->get_result_identifier());
						$explode_r_prev = explode(': ', $prev_buffer_item->get_result_identifier());

						if(count($explode_r) > 1 && $explode_r[0] != $explode_r_prev[0])
						{
							// This case wards against it looking like a regression between multiple systems on a Phoromatic Tracker
							// The premise is the format is "SYSTEM NAME: DATE" so match up SYSTEM NAME's
							continue;
						}
					}

					$this_regression_marker = new pts_test_result_regression_marker($result_object, $prev_buffer_item, $buffer_item, $test_index);

					if($show_only_active_regressions)
					{
						foreach($this_test_regressions as $index => &$regression_marker)
						{
							if(abs(1 - ($regression_marker->get_base_value() / $this_regression_marker->get_regressed_value())) < 0.04)
							{
								// 1% tolerance, regression seems to be corrected
								unset($this_test_regressions[$index]);
								$this_regression_marker = null;
								break;
							}
						}
					}

					if($this_regression_marker != null)
					{
						array_push($this_test_regressions, $this_regression_marker);
					}
				}

				$prev_buffer_item = $buffer_item;
			}

			foreach($this_test_regressions as &$regression_marker)
			{
				array_push($regressions, $regression_marker);
			}
		}

		return $regressions;
	}
}

?>
