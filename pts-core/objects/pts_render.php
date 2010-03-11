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
	public static function render_graph(&$r_o, $save_as = false, $suite_name = null, $pts_version = PTS_VERSION, $extra_attributes = null)
	{
		$result_format = $r_o->get_format();

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
		eval("\$graph = new " . $graph_type . "(\$r_o);");


		if(isset($extra_attributes["regression_marker_threshold"]))
		{
			$graph->markResultRegressions($extra_attributes["regression_marker_threshold"]);
		}

		switch($result_format)
		{
			case "LINE_GRAPH":
			case "BAR_ANALYZE_GRAPH":
				//$graph->hideGraphIdentifiers();
				foreach($r_o->get_result_buffer()->get_buffer_items() as $buffer_item)
				{
					$graph->loadGraphValues(explode(',', $buffer_item->get_result_value()), $buffer_item->get_result_identifier());
				}

				$scale_special = $r_o->get_scale_special();
				if(!empty($scale_special) && count(($ss = explode(',', $scale_special))) > 0)
				{
					$graph->loadGraphIdentifiers($ss);
				}
				break;
			default:
				// TODO: should be able to load pts_test_result_buffer_item objects more cleanly into pts_Graph
				$identifiers = array();
				$values = array();
				$raw_values = array();

				foreach($r_o->get_result_buffer()->get_buffer_items() as $buffer_item)
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

		$graph->loadGraphProportion($r_o->get_proportion());
		$graph->loadGraphVersion("Phoronix Test Suite " . $pts_version);

		$graph->addInternalIdentifier("Test", $r_o->get_test_name());
		$graph->addInternalIdentifier("Identifier", $suite_name);

		if(function_exists("pts_current_user"))
		{
			$graph->addInternalIdentifier("User", pts_current_user());
		}

		if($save_as)
		{
			$graph->saveGraphToFile($save_as);
		}

		if(function_exists("pts_set_assignment"))
		{
			pts_set_assignment("LAST_RENDERED_GRAPH", $graph);
		}

		return $graph->renderGraph();
	}
}

?>
