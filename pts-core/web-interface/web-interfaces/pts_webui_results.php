<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013, Phoronix Media
	Copyright (C) 2013, Michael Larabel

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
		$results = array();
		foreach(pts_file_io::glob(PTS_SAVE_RESULTS_PATH . '*/composite.xml') as $composite)
		{
			$results[filemtime($composite)] = basename(dirname($composite));
		}
		krsort($results);

		$sections = array(
			mktime(date('H'), date('i') - 10, 0, date('n'), date('j')) => 'Just Now',
			mktime(0, 0, 0, date('n'), date('j')) => 'Today',
			mktime(0, 0, 0, date('n'), date('j') - date('N') + 1) => 'This Week',
			mktime(0, 0, 0, date('n'), 1) => 'This Month',
			mktime(0, 0, 0, date('n') - 1, 1) => 'Last Month',
			mktime(0, 0, 0, 1, 1) => 'This Year',
			mktime(0, 0, 0, 1, 1, date('Y') - 1) => 'Last Year',
			);


		echo '<div style="overflow: hidden;">';
		$section = current($sections);
		foreach($results as $result_time => &$result)
		{
			if($result_time < key($sections))
			{
				while($result_time < key($sections) && $section !== false)
				{
					$section = next($sections);
				}

				if($section === false)
				{
					break;
				}

				echo '</div>' . PHP_EOL . '<h2>' . current($sections) . '</h2>' . PHP_EOL . '<div style="overflow: hidden;">';
			}

			$result_file = new pts_result_file($result);

			echo '<a href="?result/' . $result . '"><div class="pts_blue_bar"><strong>' . $result_file->get_title() . '</strong><br /><span style="font-size: 10px;">' . date('j M', $result_time) . ' - ' . pts_strings::plural_handler($result_file->get_system_count(), 'System') . ' - ' . pts_strings::plural_handler($result_file->get_test_count(), 'Result') . '</span></div></a>';
		}
		echo '</div>';
	}
}

?>
