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
class analyze_all_runs implements pts_option_interface
{
	const doc_section = 'Result Management';
	const doc_description = 'This option will generate a candlestick graph showing the distribution of results from all trial runs. The candlestick graph is similar to the Japanese candlestick charts used by the financial industry, except instead of representing stock data it is numerical result data from all trial runs.\n\nThe tip of the upper-wick represents the highest value of the test runs with the tip of the lower-wick representing the lowest value of all test runs. The upper-edge of the candle body represents the first or last run value and the lower-edge represents the first or last run value. Lastly, if the last run value is less than the first run value, the candle body is the same color as the graph background, otherwise the last run value is greater.';
	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($args)
	{
		pts_client::regenerate_graphs($args[0], 'The ' . $args[0] . ' result file graphs have been re-rendered to show all test runs.', array('graph_render_type' => 'HORIZONTAL_BOX_PLOT'));
	}
}
?>
