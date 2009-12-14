<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions_render.php: Functions needed for rendering graphs

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

function pts_render_graph(&$r_o, $save_as = false, $suite_name = null, $pts_version = PTS_VERSION)
{
	$version = $r_o->get_version();
	$name = $r_o->get_name() . (isset($version[2]) ? " v" . $version : "");
	$result_format = $r_o->get_format();
	$result_buffer_items = $r_o->get_result_buffer()->get_buffer_items();

	if(getenv("REVERSE_GRAPH_ORDER"))
	{
		// Plot results in reverse order on graphs if REVERSE_GRAPH_ORDER env variable is set
		$result_buffer_items = array_reverse($result_buffer_items);
	}

	// TODO: cleanup the below code
	if($result_format == "LINE_GRAPH" || $result_format == "BAR_ANALYZE_GRAPH")
	{
		if($result_format == "LINE_GRAPH")
		{
			$t = new pts_LineGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
		}
		else
		{
			$t = new pts_BarGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
		}

		//$t->hideGraphIdentifiers();
		foreach($result_buffer_items as &$buffer_item)
		{
			$t->loadGraphValues(explode(",", $buffer_item->get_result_value()), $buffer_item->get_result_identifier());
		}

		$scale_special = $r_o->get_scale_special();
		if(!empty($scale_special) && count(($ss = explode(",", $scale_special))) > 0)
		{
			$t->loadGraphIdentifiers($ss);
		}
	}
	else
	{
		switch($result_format)
		{
			case "PASS_FAIL":
				$t = new pts_PassFailGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
				break;
			case "MULTI_PASS_FAIL":
				$t = new pts_MultiPassFailGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
				break;
			case "TEST_COUNT_PASS":
				$t = new pts_TestCountPassGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
				break;
			case "IMAGE_COMPARISON":
				$t = new pts_ImageComparisonGraph($name, $r_o->get_attributes());
				break;
			default:
				if((function_exists("pts_read_assignment") && pts_read_assignment("GRAPH_RENDER_TYPE") == "CANDLESTICK") || (defined("GRAPH_RENDER_TYPE") && GRAPH_RENDER_TYPE == "CANDLESTICK"))
				{
					$t = new pts_CandleStickGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
				}
				else if((function_exists("pts_read_assignment") && pts_read_assignment("GRAPH_RENDER_TYPE") == "LINE_GRAPH") || (defined("GRAPH_RENDER_TYPE") && GRAPH_RENDER_TYPE == "LINE_GRAPH"))
				{
					$t = new pts_LineGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
				}
				else
				{
					$t = new pts_BarGraph($name, $r_o->get_attributes(), $r_o->get_scale_formatted());
				}
				break;
		}

		// TODO: this code below is dirty, should be able to load pts_test_result_buffer_item objects more cleanly into pts_Graph
		$identifiers = array();
		$values = array();
		$raw_values = array();

		foreach($result_buffer_items as &$buffer_item)
		{
			array_push($identifiers, $buffer_item->get_result_identifier());
			array_push($values, $buffer_item->get_result_value());
			array_push($raw_values, $buffer_item->get_result_raw());
		}

		$t->loadGraphIdentifiers($identifiers);
		$t->loadGraphValues($values);
		$t->loadGraphRawValues($raw_values);
	}

	$t->loadGraphProportion($r_o->get_proportion());
	$t->loadGraphVersion("Phoronix Test Suite " . $pts_version);

	$t->addInternalIdentifier("Test", $r_o->get_test_name());
	$t->addInternalIdentifier("Identifier", $suite_name);

	if(function_exists("pts_current_user"))
	{
		$t->addInternalIdentifier("User", pts_current_user());
	}

	if($save_as)
	{
		$t->saveGraphToFile($save_as);
	}

	if(function_exists("pts_set_assignment"))
	{
		pts_set_assignment("LAST_RENDERED_GRAPH", $t);
	}

	return $t->renderGraph();
}

?>
