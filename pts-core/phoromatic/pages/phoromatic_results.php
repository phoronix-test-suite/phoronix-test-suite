<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2014, Phoronix Media
	Copyright (C) 2008 - 2014, Michael Larabel

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


class phoromatic_results implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Test Schedules';
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
			$main = null;

			if(isset($PATH[0]))
			{
				$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE AccountID = :account_id AND UploadID = :upload_id LIMIT 1');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':upload_id', $PATH[0]);
				$result = $stmt->execute();
				$row = $result->fetchArray();

				if($row)
				{
					$main .= '<h1>' . $row['Title'] . '</h1>';
					pts_openbenchmarking::clone_openbenchmarking_result($row['OpenBenchmarkingID']);
					$result_file = new pts_result_file($row['OpenBenchmarkingID']);

					$extra_attributes = array();
					$intent = null;

					if($result_file->get_system_count() == 1 || ($intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true)))
					{
						$table = new pts_ResultFileCompactSystemsTable($result_file, $intent);

					}
					else
					{
						$table = new pts_ResultFileSystemsTable($result_file);
					}

					$main .= '<p class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';

					foreach($result_file->get_result_objects() as $i => $result_object)
					{
						$main .= '<h2><a name="r-' . $i . '"></a>' . $result_object->test_profile->get_title() . '</h2>';
						$main .= '<p class="result_object">';
						$main .= pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes);
						$main .= '</p>';
					}
				}
			}

			if($main == null)
			{
				$main = '<h1>Test Results</h1>';
				$main .= '<div class="pts_phoromatic_info_box_area">';
				$main .= '<div style="float: left; width: 100%;"><ul><li><h1>Recent Test Results</h1></li>';
				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, UploadID, UploadTime FROM phoromatic_results WHERE AccountID = :account_id ORDER BY UploadTime DESC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$test_result_result = $stmt->execute();
				$results = 0;
				while($test_result_row = $test_result_result->fetchArray())
				{
					if($results > 100)
					{
						break;
					}
					$main .= '<a href="?results/' . $test_result_row['UploadID'] . '"><li>' . $test_result_row['Title'] . '<br /><em>' . phoromatic_system_id_to_name($test_result_row['SystemID']) . ' - ' . phoromatic_user_friendly_timedate($test_result_row['UploadTime']) .  '</em></li></a>';
					$results++;

				}
				if($results == 0)
				{
					$main .= '<li class="light" style="text-align: center;">No Results Found</li>';
				}
				$main .= '</ul></div>';
				$main .= '</div><h3>TODO A lot of other result analysis functionality powered by OpenBenchmarking.org to come in next few days...';
			}

			echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
			echo phoromatic_webui_footer();
	}
}

?>
