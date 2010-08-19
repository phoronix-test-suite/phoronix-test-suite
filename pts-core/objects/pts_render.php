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
			$result_table = false;
			pts_tracker::compact_result_file_test_object($result_object, $result_table, $result_file->is_multi_way_inverted());
		}

		$result_format = $result_object->test_profile->get_result_format();

		switch($result_format)
		{
			case "LINE_GRAPH":
				$graph_type = "pts_LineGraph";
				break;
			case "BAR_ANALYZE_GRAPH":
				$graph_type = "pts_BarGraph";
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
				if(PTS_MODE == "CLIENT" && function_exists("pts_read_assignment") && pts_is_assignment("GRAPH_RENDER_TYPE"))
				{
					$requested_graph_type = pts_read_assignment("GRAPH_RENDER_TYPE");
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
						$graph_type = "pts_BarGraph";
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
				}

				$scale_special = $result_object->get_scale_special();
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
			$chart = new pts_Chart($result_file);
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

					$chart = new pts_Chart($result_file, null, $table_keys);
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

			if($graph->skip_graph == false)
			{
				$graph->saveGraphToFile($save_to_dir . "/result-graphs/visualize.BILDE_EXTENSION");
				$graph->renderGraph();
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
}

?>
