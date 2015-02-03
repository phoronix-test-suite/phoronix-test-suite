<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

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


class phoromatic_build_suite implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Build Custom Test Suite';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	public static function render_page_process($PATH)
	{
		echo phoromatic_webui_header_logged_in();
		$main = '<h1>Build Suite</h1><p>A test suite in the realm of the Phoronix Test Suite, OpenBenchmarking.org, and Phoromatic is <strong>a collection of test profiles with predefined settings</strong>. Establishing a test suite makes it easy to run repetitive testing on the same set of test profiles by simply referencing the test suite name.</p>';
		$main .= '<form action="' . $_SERVER['REQUEST_URI'] . '" name="add_suite" id="add_suite" method="post" onsubmit="return validate_suite();">
		<h3>Title:</h3>
		<p><input type="text" name="suite_title" /></p>
		<h3>Description:</h3>
		<p><textarea name="suite_description" id="suite_description" cols="50" rows="3"></textarea></p>
		<h3>Add A Test</h3>';

		$main .= '<select name="add_to_schedule_select_test" id="add_to_schedule_select_test" onchange="phoromatic_schedule_test_details(\'\');">';
		foreach(pts_openbenchmarking::available_tests() as $test)
		{
			$main .= '<option value="' . $test . '">' . $test . '</option>';
		}
		$main .= '</select>';
		$main .= '<p><div id="test_details"></div></p>';
		$main .= '<p align="right"><input name="submit" value="Create Suite" type="submit" onclick="return pts_rmm_validate_suite();" /></p>';

		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
