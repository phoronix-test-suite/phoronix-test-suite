<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2015, Phoronix Media
	Copyright (C) 2013 - 2015, Michael Larabel

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
		echo '<div style="background: #CCC; padding: 10px; margin: 10px 20px;">Thanks for trying out the Phoronix Test Suite GUI. With Phoronix Test Suite 5.0 the GUI is still considered in an <strong>experimental / tech preview state</strong>. The GUI should be more end-user friendly and reach feature parity with the command-line interface in forthcoming releases. Your feedback is appreciated on the GUI while the command-line interface continues to be our primary focus along with remotely-managed enterprise features like <a href="http://www.phoromatic.com/">Phoromatic</a> and <a href="http://openbenchmarking.org/">OpenBenchmarking.org</a>. <a href="/early">Read more details on the GUI</a>.</div>';

		echo '<h1>' . pts_core::program_title() . '</h1>';

		echo '<div id="pts_side_pane">';

			$hw_component_modal = array('CPU' => phodevi::read_property('cpu', 'model'), 'Motherboard' => phodevi::read_property('motherboard', 'identifier'), 'Memory' => phodevi::read_property('memory', 'identifier'), 'Disk' => phodevi::read_property('disk', 'identifier'), 'GPU' => phodevi::read_property('gpu', 'model'));

			echo '<ul>';
			foreach($hw_component_modal as $type => $component)
			{
				echo '<a href="/?component/' . $type . '"><li>' . $component . '</li></a>';
			}
			echo '</ul>';
			echo '<hr />';

			$sw_component_modal = array(1 => phodevi::read_property('system', 'operating-system'), 2 => phodevi::read_property('system', 'kernel-string'), 3 => phodevi::read_property('system', 'display-driver-string'), 4 => 'OpenGL ' . phodevi::read_property('system', 'opengl-driver'), 5 => phodevi::read_property('system', 'compiler'));

			echo '<ul>';
			foreach($sw_component_modal as $type => $component)
			{
				echo '<a href="/?component/Software"><li>' . $component . '</li></a>';
			}
			echo '</ul>';

			echo '<div class="pts_pane_window"><strong>OpenBenchmarking.org</strong><br />Log-in to gain access to additional features.</div>';

			echo '<ul>';
			echo '<a href="/?settings"><li>Software Settings</li></a>';
			echo '<a href="/?about"><li>About The Phoronix Test Suite</li></a>';
			echo '</ul>';

		echo '</div>';

		echo '<div id="pts_search_bar">';
		echo 'SEARCH: <input type="text" size="30" id="pts_search" name="search" onkeydown="if(event.keyCode == 13) { if(document.getElementById(\'pts_search\').value.length < 3) { alert(\'Please enter a longer search query.\'); return false; } else { window.location.href = \'/?search/\' + document.getElementById(\'pts_search\').value; } return false; }" />';
		echo '</div>';

		// Graphs
		echo '<div id="svg_graphs" style="margin: 10px 0; text-align: right;"></div>';
		echo '<div style="overflow: hidden;">';

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
		</script>';
	}
}

?>
