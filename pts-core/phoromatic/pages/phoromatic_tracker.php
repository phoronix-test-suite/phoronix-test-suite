<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2015, Phoronix Media
	Copyright (C) 2014 - 2015, Michael Larabel

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


class phoromatic_tracker implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Result Tracker';
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

		if(isset($PATH[0]) && !empty($PATH[0]))
		{
			ini_set('memory_limit', '4G');
			$cut_duration = 30;
			$stmt = phoromatic_server::$db->prepare('SELECT UploadID, UploadTime, ScheduleID, Trigger, SystemID FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY UploadTime DESC');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':schedule_id', $PATH[0]);
			$test_result_result = $stmt->execute();
			$cutoff_time = is_numeric($cut_duration) ? strtotime('today -' . $cut_duration . ' days') : false;

			$show_only_latest_systems = array();
			$result_file = array();
			while($test_result_result && $row = $test_result_result->fetchArray())
			{
				if($cutoff_time !== false && strtotime($row['UploadTime']) < $cutoff_time)
					break;

				$composite_xml = phoromatic_server::phoromatic_account_result_path($_SESSION['AccountID'], $row['UploadID']) . 'composite.xml';
				if(!is_file($composite_xml))
				{
					continue;
				}

				// Add to result file
				$system_name = phoromatic_server::system_id_to_name($row['SystemID']) . ': ' . $row['Trigger'];
				array_push($result_file, new pts_result_merge_select($composite_xml, null, $system_name));
				if(!isset($show_only_latest_systems[$_SESSION['AccountID'] . $row['SystemID']]))
				{
					$show_only_latest_systems[$_SESSION['AccountID'] . $row['SystemID']] = new pts_result_merge_select($composite_xml, null, $system_name);
				}
			}

			if(count($result_file) < 21)
			{
				$show_only_latest_systems = null;
			}

			$writer = new pts_result_file_writer(null);
			$attributes = array('new_result_file_title' => phoromatic_schedule_id_to_name($row['ScheduleID']));
			pts_merge::merge_test_results_process($writer, $result_file, $attributes);
			$result_file = new pts_result_file($writer->get_xml());
			$extra_attributes = array('reverse_result_buffer' => true, 'force_simple_keys' => true);


			$main .= '<h1>' . $result_file->get_title() . '</h1>';

			if(!empty($show_only_latest_systems) && false) // TODO XXX: Finish up
			{
				$main .= '<h2>Latest System States:</h2>';
				$writer2 = new pts_result_file_writer(null);
				$a = array();
				pts_merge::merge_test_results_process($writer2, $show_only_latest_systems, $a);
				$result_file2 = new pts_result_file($writer2->get_xml());
				$table = new pts_ResultFileSystemsTable($result_file2);
			}
			else
			{
				if($result_file->get_system_count() == 1 || ($intent = pts_result_file_analyzer::analyze_result_file_intent($result_file, $intent, true)))
				{
					$table = new pts_ResultFileCompactSystemsTable($result_file, $intent);
				}
				else
				{
					$table = new pts_ResultFileSystemsTable($result_file);
				}
			}

			$main .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';

			$table = new pts_ResultFileTable($result_file, $intent);
			$main .= '<p style="text-align: center; overflow: auto;" class="result_object">' . pts_render::render_graph_inline_embed($table, $result_file, $extra_attributes) . '</p>';

			$main .= '<div id="pts_results_area">';
			foreach($result_file->get_result_objects((isset($_POST['show_only_changed_results']) ? 'ONLY_CHANGED_RESULTS' : -1)) as $i => $result_object)
			{
				$main .= '<h2><a name="r-' . $i . '"></a><a name="' . $result_object->get_comparison_hash(true, false) . '"></a>' . $result_object->test_profile->get_title() . '</h2>';
				$main .= '<p class="result_object">';
				$main .= pts_render::render_graph_inline_embed($result_object, $result_file, $extra_attributes);
				$main .= '</p>';
			}
			$main .= '</div>';

		}
		else if(empty($PATH))
		{
			$main .= '<h1>Phoromatic Tracker</h1>
					<p>The Phoromatic Tracker will show result schedules that have enough uploaded test results from the associated systems to begin providing concise overviews of performance over time.</p>
					<div class="pts_phoromatic_info_box_area">
					<ul>
						<li><h1>Trackable Results</h1></li>';

					$stmt = phoromatic_server::$db->prepare('SELECT Title, ScheduleID, Description, RunTargetSystems, RunTargetGroups, RunAt, ActiveOn, (SELECT COUNT(*) FROM phoromatic_results WHERE ScheduleID = phoromatic_schedules.ScheduleID) AS UploadedResultCount FROM phoromatic_schedules WHERE AccountID = :account_id AND State >= 1 ORDER BY Title ASC');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$result = $stmt->execute();
					$row = $result->fetchArray();

					if($row == false)
					{
						$main .= '<li class="light" style="text-align: center;">No Relevant Schedules Found</li>';
					}
					else
					{
						do
						{
							if($row['UploadedResultCount'] > (($row['RunTargetSystems'] + $row['RunTargetGroups'] + 1) * 7))
							{
								$stmt_tests = phoromatic_server::$db->prepare('SELECT COUNT(*) AS TestCount FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY TestProfile ASC');
								$stmt_tests->bindValue(':account_id', $_SESSION['AccountID']);
								$stmt_tests->bindValue(':schedule_id', $row['ScheduleID']);
								$result_tests = $stmt_tests->execute();
								$row_tests = $result_tests->fetchArray();
								$test_count = !empty($row_tests) ? $row_tests['TestCount'] : 0;

								$group_count = empty($row['RunTargetGroups']) ? 0 : count(explode(',', $row['RunTargetGroups']));
								$main .= '<a href="?tracker/' . $row['ScheduleID'] . '"><li>' . $row['Title'] . '<br /><table><tr><td>' . pts_strings::plural_handler(count(phoromatic_server::systems_associated_with_schedule($_SESSION['AccountID'], $row['ScheduleID'])), 'System') . '</td><td>' . pts_strings::plural_handler($group_count, 'Group') . '</td><td>' . pts_strings::plural_handler($test_count, 'Test') . '</td><td>' . pts_strings::plural_handler($row['UploadedResultCount'], 'Result') . ' Total</td></tr></table></li></a>';
							}
						}
						while($row = $result->fetchArray());
					}


			$main .= '</ul>
			</div>';
		}

		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
