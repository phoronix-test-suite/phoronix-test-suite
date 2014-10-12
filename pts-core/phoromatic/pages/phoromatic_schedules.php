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


class phoromatic_schedules implements pts_webui_interface
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

		if(!empty($PATH[0]) && is_numeric($PATH[0]))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':schedule_id', $PATH[0]);
			$result = $stmt->execute();
			$row = $result->fetchArray();

			if(empty($row))
			{
				$main = '<h1>Test Schedules</h1>';
				$main .= '<h3>No Resource Found</h3>';
			}
			else
			{

				if(isset($_POST['add_to_schedule_select_test']))
				{
					$name = $_POST['add_to_schedule_select_test'];
					$args = array();
					$args_name = array();

					foreach($_POST as $i => $v)
					{
						if(substr($i, 0, 12) == 'test_option_' && substr($i, -9) != '_selected')
						{
							array_push($args, $v);
							array_push($args_name, $_POST[$i . '_selected']);
						}
					}

					$args_name = implode(' - ', $args_name);
					$args = implode(' ', $args);

					if(!empty($name))
					{
						$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_schedules_tests (AccountID, ScheduleID, TestProfile, TestArguments, TestDescription) VALUES (:account_id, :schedule_id, :test_profile, :test_arguments, :test_description)');
						$stmt->bindValue(':account_id', $_SESSION['AccountID']);
						$stmt->bindValue(':schedule_id', $PATH[0]);
						$stmt->bindValue(':test_profile', $name);
						$stmt->bindValue(':test_arguments', $args);
						$stmt->bindValue(':test_description', $args_name);
						$result = $stmt->execute();
					}
				}
				else if(isset($PATH[1]) && $PATH[1] == 'remove' && !empty($PATH[2]))
				{
					// REMOVE TEST
					$to_remove = explode(PHP_EOL, base64_decode($PATH[2]));
					$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND TestProfile = :test AND TestArguments = :test_args');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':schedule_id', $PATH[0]);
					$stmt->bindValue(':test', $to_remove[0]);
					$stmt->bindValue(':test_args', $to_remove[1]);
					$result = $stmt->execute();
				}
				else if(isset($PATH[1]) && in_array($PATH[1], array('activate', 'deactivate')))
				{
					switch($PATH[1])
					{
						case 'deactivate':
							$new_state = 0;
							break;
						case 'activate':
						default:
							$new_state = 1;
							break;
					}

					// REMOVE TEST
					$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_schedules SET State = :new_state WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':schedule_id', $PATH[0]);
					$stmt->bindValue(':new_state', $new_state);
					$result = $stmt->execute();
					$row['State'] = $new_state;
				}


				$main = '<h1>' . $row['Title'] . '</h1>';
				$main .= '<h3>' . $row['Description'] . '</h3>';
				$main .= '<p>This schedule was last modified on <strong>' . date('j F Y \a\t H:i', strtotime($row['LastModifiedOn'])) . '</strong> by <strong>' . $row['LastModifiedBy'] . '</strong>.';

				if(!PHOROMATIC_USER_IS_VIEWER)
				{
					$main .= '<p><a href="?sched/' . $PATH[0] . '">Edit Schedule</a> | ';

					if($row['State'] == 1)
					{
						$main .= '<a href="?schedules/' . $PATH[0] . '/deactivate">Deactivate Schedule</a>';
					}
					else
					{
						$main .= '<a href="?schedules/' . $PATH[0] . '/activate">Activate Schedule</a>';
					}

					$main .= '</p>';
				}

				$main .= '<hr />';
				$main .= '<h2>Schedule</h2>';
				if(!empty($row['ActiveOn']))
				{
					$active_days = explode(',', $row['ActiveOn']);
					$week = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');
					foreach($active_days as $i => &$day)
					{
						if(!isset($week[$day]))
						{
							unset($active_days[$i]);
						}
						else
						{
							$day = $week[$day];
						}
					}

					switch(count($active_days))
					{
						case 2:
							$day_show = implode(' and ', $active_days);
							break;
						default:
							$day_show = implode(', ', $active_days);
							break;
					}

					$main .= '<p>This test is scheduled to run every <strong>' . $day_show . '</strong> at <strong>' . str_replace('.', ':', $row['RunAt']) . '</strong>.';
				}
				else
				{
					$main .= '<p>This test schedule is not currently set to run a pre-defined time-based schedule.</p>';
				}
				$trigger_url = 'http://' . phoromatic_web_socket_server_ip() . '/event.php?type=trigger&user=' . $_SESSION['UserName'] . '&public_key=' . $row['PublicKey'] . '&trigger=XXX';
				$main .= '<p>This test schedule can be manually triggered to run at any time by calling <a href="#">' . $trigger_url . '</a> where <em>XXX</em> is the trigger value to be used (if relevant, such as a time-stamp, Git/SVN commit number or hash, etc.)';
				$main .= '<hr />';
				$main .= '<h2>Tests To Run</h2>';

				$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY TestProfile ASC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':schedule_id', $PATH[0]);
				$result = $stmt->execute();

				$test_count = 0;
				while($row = $result->fetchArray())
				{
					$test_count++;
					$main .= '<h3>' . $row['TestProfile'] . ($row['TestDescription'] != null ? ' - <em>' . $row['TestDescription'] . '</em>' : '') . (!PHOROMATIC_USER_IS_VIEWER ? ' <a href="?schedules/' . $PATH[0] . '/remove/' . base64_encode(implode(PHP_EOL, array($row['TestProfile'], $row['TestArguments']))) . '">Remove Test</a>' : null) . '</h3>';
				}

				if($test_count == 0)
				{
					$main .= '<h3 style="text-transform: uppercase;">No tests have been added yet for this test schedule.</h3>';
				}

				if(!PHOROMATIC_USER_IS_VIEWER)
				{
					$main .= '<hr /><h2>Add A Test</h2>';
					$main .= '<form action="?schedules/' . $PATH[0] . '" name="add_test" id="add_test" method="post">';
					$main .= '<select name="add_to_schedule_select_test" id="add_to_schedule_select_test" onchange="phoromatic_schedule_test_details();">';
					foreach(pts_openbenchmarking::available_tests() as $test) {
						$main .= '<option value="' . $test . '">' . $test . '</option>';
					}
					$main .= '</select>';
					$main .= '<p><div id="test_details"></div></p>';
					$main .= '</form>';
				}

				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, UploadID, UploadTime FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY UploadTime DESC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':schedule_id', $PATH[0]);
				$test_result_result = $stmt->execute();
				$test_result_row = $test_result_result->fetchArray();

				if($test_result_row)
				{
					$main .= '<hr /><div class="pts_phoromatic_info_box_area">';
					$main .= '<div style="float: left; width: 100%;"><ul><li><h1>Recent Test Results For This Schedule</h1></li>';
					$results = 0;
					do
					{
						if($results > 100)
						{
							break;
						}
						$main .= '<a href="?results/' . $test_result_row['UploadID'] . '"><li>' . $test_result_row['Title'] . '<br /><em>' . phoromatic_system_id_to_name($test_result_row['SystemID']) . ' - ' . phoromatic_user_friendly_timedate($test_result_row['UploadTime']) .  '</em></li></a>';
						$results++;

					}
					while($test_result_row = $test_result_result->fetchArray());
					$main .= '</ul></div>';
					$main .= '</div>';
				}
			}

			echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
			echo phoromatic_webui_footer();
			return;
		}


		$main = '<h1>Test Schedules</h1>
			<h2>Current Schedules</h2>';


			$main .= '<div class="pts_phoromatic_info_box_area">

				<div style="float: left; width: 100%;">
					<ul>
						<li><h1>Active Test Schedules</h1></li>';

					$stmt = phoromatic_server::$db->prepare('SELECT Title, ScheduleID, Description, RunTargetSystems, RunTargetGroups FROM phoromatic_schedules WHERE AccountID = :account_id AND State >= 1 ORDER BY Title ASC');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$result = $stmt->execute();
					$row = $result->fetchArray();

					if($row == false)
					{
						$main .= '<li class="light" style="text-align: center;">No Schedules Found</li>';
					}
					else
					{
						do
						{
							$system_count = empty($row['RunTargetSystems']) ? 0 : count(explode(',', $row['RunTargetSystems']));
							$group_count = empty($row['RunTargetGroups']) ? 0 : count(explode(',', $row['RunTargetGroups']));
							$main .= '<a href="?schedules/' . $row['ScheduleID'] . '"><li>' . $row['Title'] . '<br /><em><strong>' . $system_count . ' Systems | ' . $group_count . ' Groups</strong> ' . $row['Description'] . ' </em></li></a>';
						}
						while($row = $result->fetchArray());
					}


			$main .= '</ul>
				</div>
			</div>';

			if(!PHOROMATIC_USER_IS_VIEWER)
			{
				$main .= '
				<hr />
				<h2>Create A Schedule</h2>
				<p><a href="?sched">Create a schedule</a> followed by adding tests/suites to run for that schedule on the selected systems.</p>';
			}
			echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
			echo phoromatic_webui_footer();
	}
}

?>
