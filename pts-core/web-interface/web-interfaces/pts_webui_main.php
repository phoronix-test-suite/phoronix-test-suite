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


class pts_webui_main implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Available Tests';
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
		echo '<h1>' . pts_title(false) . '</h1>';

		echo '<div id="pts_side_pane">';

			$hw_component_modal = array('CPU' => phodevi::read_property('cpu', 'model'), 'Motherboard' => phodevi::read_property('motherboard', 'identifier'), 'Disk' => phodevi::read_property('disk', 'identifier'), 'GPU' => phodevi::read_property('gpu', 'model'));

			echo '<ul>';
			foreach($hw_component_modal as $type => $component)
			{
				echo '<a href="/?component/' . $type . '"><li>' . $component . '</li></a>';
			}
			echo '</ul>';
			echo '<hr />';

			$sw_component_modal = array('OS' => phodevi::read_property('system', 'operating-system'), 'OS' => phodevi::read_property('system', 'kernel-string'), 'Display Driver' => phodevi::read_property('system', 'display-driver-string'), 'OpenGL' => phodevi::read_property('system', 'opengl-driver'), 'Compiler' => phodevi::read_property('system', 'compiler'), 'File-System' => phodevi::read_property('system', 'filesystem'));

			echo '<ul>';
			foreach($sw_component_modal as $type => $component)
			{
				echo '<a href="/?component/' . $type . '"><li>' . $component . '</li></a>';
			}
			echo '</ul>';

			echo '<div class="pts_pane_window">Log-in to OpenBenchmarking.org to gain access to more functionality.</div>';

		echo '</div>';

		echo '<div style="text-align: right; margin-bottom: 10px;">';
		echo 'SEARCH: <input type="text" size="30" id="pts_search" name="search" onkeydown="if(event.keyCode == 13) { if(document.getElementById(\'pts_search\').value.length < 3) { alert(\'Please enter a longer search query.\'); return false; } else { window.location.href = \'/?search/\' + document.getElementById(\'pts_search\').value; } return false; }" />';
		echo '</div>';

		// Graphs
		echo '<div id="svg_graphs" style="margin: 10px 0; text-align: right;"></div>';
		echo '<div style="float: right; overflow: hidden; width: auto;">';

		echo '<div class="pts_list_box">';

			$results = pts_tests::test_results_by_date();
			$result_count = count($results);
			$results = array_slice($results, 0, 10, true);
			echo '<ol>';
			echo '<li><u>Recent Benchmark Results</u></li>';
			foreach($results as $result)
			{
				$result_file = new pts_result_file($result);
				echo '<a href="?result/' . $result . '"><li>' . $result_file->get_title() . '</li></a>';
			}
			echo '<a href="?results"><li><strong>' . $result_count . ' Results Saved</strong></li></a>';
			echo '</ol>';
		echo '</div>';

		echo '<div class="pts_list_box">';

			$tests = pts_openbenchmarking_client::recently_updated_tests(10);
			echo '<ol>';
			echo '<li><u>Recently Updated Tests</u></li>';
			foreach($tests as $test)
			{
				$test_profile = new pts_test_profile($test);
				echo '<a href="?test/' . $test . '"><li>' . $test_profile->get_title() . '</li></a>';
			}
			echo '<a href="?tests"><li><strong>' . pts_openbenchmarking_client::tests_available() . ' Tests Available</strong></li></a>';
			echo '</ol>';
		echo '</div>';

		echo '<div class="pts_list_box">';

			$tests = pts_openbenchmarking_client::popular_tests(10);
			echo '<ol>';
			echo '<li><u>Most Popular Tests</u></li>';
			foreach($tests as $test)
			{
				$test_profile = new pts_test_profile($test);
				echo '<a href="?test/' . $test . '"><li>' . $test_profile->get_title() . '</li></a>';
			}
			echo '<a href="?tests"><li><strong>' . pts_openbenchmarking_client::tests_available() . ' Tests Available</strong></li></a>';
			echo '</ol>';
		echo '</div>';

		echo '</div>';

		echo '<script text="text/javascript">

			pts_web_socket.add_onopen_event("user-svg-system-graphs");
			setInterval(function(){if(pts_web_socket.is_connected()) { pts_web_socket.send("user-svg-system-graphs"); }},1000);
			pts_web_socket.add_onmessage_event("svg_graphs", "update_svg_graph_space");

			function update_svg_graph_space(jsonr)
			{
				document.getElementById("svg_graphs").innerHTML = jsonr.pts.msg.contents;
			}

		</script>';
	}
}

?>
