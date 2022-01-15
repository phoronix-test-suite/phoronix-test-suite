<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2021, Phoronix Media
	Copyright (C) 2021, Michael Larabel

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

class phoromatic_r_basic_suite_details implements pts_webui_interface
{
	public static function page_title()
	{
		return '';
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
		phoromatic_quit_if_invalid_input_found(array('ts'));
		$ts = $_GET['ts'];
		$ts_file = phoromatic_server::find_suite_file($_SESSION['AccountID'], $ts);
		$test_suite = new pts_test_suite($ts_file);
		$name = $test_suite->get_title();
		$description = $test_suite->get_description();

		echo '<h2>' . $name . '</h2>';
		echo '<p><em>' . $description . '</em></p>';
		$test_suite->sort_contained_tests();
		foreach($test_suite->get_contained_test_result_objects() as $tro)
		{
			echo '<p><strong>' . $tro->test_profile->get_title() . ' [' . $tro->test_profile->get_identifier() . ']</strong><br />';
			echo $tro->get_arguments_description() . '</p>' . PHP_EOL;
		}
		if(stripos($_SERVER['HTTP_REFERER'], '?schedules') !== false)
		{
			echo '<input type="hidden" name="suite_add" value="' . $ts . '" />';
			echo '<br /><br /><p><input name="submit" value="Add" type="submit" onclick="" /></p>';
		}
	}
}

?>
