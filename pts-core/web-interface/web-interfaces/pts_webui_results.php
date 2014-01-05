<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2014, Phoronix Media
	Copyright (C) 2013 - 2014, Michael Larabel

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


class pts_webui_results implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Test Results';
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
		$results = pts_tests::test_results_by_date();

		$sections = array(
			mktime(date('H'), date('i') - 10, 0, date('n'), date('j')) => 'Just Now',
			mktime(0, 0, 0, date('n'), date('j')) => 'Today',
			mktime(0, 0, 0, date('n'), date('j') - date('N') + 1) => 'This Week',
			mktime(0, 0, 0, date('n'), 1) => 'This Month',
			mktime(0, 0, 0, date('n') - 1, 1) => 'Last Month',
			mktime(0, 0, 0, 1, 1) => 'This Year',
			mktime(0, 0, 0, 1, 1, date('Y') - 1) => 'Last Year',
			);

		echo '<div id="results_linear_display" style="overflow: hidden;"></div>';
		echo '<script text="text/javascript">
			pts_web_socket.add_onopen_event("results_grouped_by_date");
			pts_web_socket.add_onmessage_event("results_grouped_by_date", "display_grouped_results_by_date");

		</script>';
	}
}

?>
