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


class phoromatic_admin_data implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Phoromatic Server Data';
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
		if($_SESSION['AdminLevel'] != -40)
		{
			header('Location: /?main');
		}
		if(isset($PATH[0]) && isset($PATH[1]))
		{
			switch($PATH[0])
			{
				case 'delete':
					if($PATH[1] == 'result')
					{
						$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_results WHERE PPRID = :pprid');
						$stmt->bindValue(':pprid', $PATH[2]);
						$result = $stmt->execute();
					}
					else if($PATH[1] == 'schedule')
					{
						$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_schedules WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
						$stmt->bindValue(':account_id', $PATH[2]);
						$stmt->bindValue(':schedule_id', $PATH[3]);
						$result = $stmt->execute();
					}
					else if($PATH[1] == 'system')
					{
						$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id');
						$stmt->bindValue(':account_id', $PATH[2]);
						$stmt->bindValue(':system_id', $PATH[3]);
						$result = $stmt->execute();
					}
					break;
			}
		}

		$main = '<h1>Phoromatic Server Data</h1>';
		$main .= '<h1>Test Results</h1>';
		$main .= '<div class="pts_phoromatic_info_box_area">';
		$main .= '<div style="height: 500px;"><ul style="max-height: 100%;"><li><h1>Recent Test Results</h1></li>';
		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed, AccountID FROM phoromatic_results ORDER BY UploadTime DESC LIMIT 100');
		$test_result_result = $stmt->execute();
		$results = 0;
		while($test_result_row = $test_result_result->fetchArray())
		{
			$main .= '<a href="?result/' . $test_result_row['PPRID'] . '"><li id="result_select_' . $test_result_row['PPRID'] . '">' . $test_result_row['Title'] . '<br /><table><tr><td>' . phoromatic_system_id_to_name($test_result_row['SystemID'], $test_result_row['AccountID']) . '</td><td>' . phoromatic_account_id_to_group_name($test_result_row['AccountID']) . '</td><td>' . phoromatic_user_friendly_timedate($test_result_row['UploadTime']) .  '</td><td><a onclick="return confirm(\'Permanently remove this test?\');" href="/?admin_data/delete/result/' . $test_result_row['PPRID'] . '">Permanently Remove</a></td></tr>
</table></li></a>';
			$results++;

		}
		if($results == 0)
		{
					$main .= '<li class="light" style="text-align: center;">No Results Found</li>';
		}
		$main .= '</ul></div>';
		$main .= '</div>';

		$main .= '<hr /><h1>Schedules</h1>';
		$main .= '<h2>Active Test Schedules</h2>';
		$main .= '<div class="pts_phoromatic_info_box_area">
				<ul>
					<li><h1>Active Test Schedules</h1></li>';
					$stmt = phoromatic_server::$db->prepare('SELECT Title, ScheduleID, Description, RunTargetSystems, RunTargetGroups, RunAt, ActiveOn, AccountID FROM phoromatic_schedules WHERE State >= 1 ORDER BY Title ASC');
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

							$main .= '<a href="#"><li>' . $row['Title'] . '<br /><table><tr><td>' . phoromatic_account_id_to_group_name($test_result_row['AccountID']) . '</td><td>' . pts_strings::plural_handler(count(phoromatic_server::systems_associated_with_schedule($row['AccountID'], $row['ScheduleID'])), 'System') . '</td><td><strong>' . phoromatic_schedule_activeon_string($row['ActiveOn'], $row['RunAt']) . '</strong></td><td><a onclick="return confirm(\'Permanently remove this schedule?\');" href="/?admin_data/delete/schedule/' . $row['AccountID'] . '/' . $row['ScheduleID'] . '">Permanently Remove</a></td></tr></table></li></a>';
						}
						while($row = $result->fetchArray());
					}
		$main .= '</ul></div>';

		$main .= '<hr /><h2>Inactive Test Schedules</h2>';
		$main .= '<div class="pts_phoromatic_info_box_area">
				<ul>
					<li><h1>Active Test Schedules</h1></li>';
					$stmt = phoromatic_server::$db->prepare('SELECT Title, ScheduleID, Description, RunTargetSystems, RunTargetGroups, RunAt, ActiveOn, AccountID FROM phoromatic_schedules WHERE State < 1 ORDER BY Title ASC');
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

							$main .= '<a href="#"><li>' . $row['Title'] . '<br /><table><tr><td>' . phoromatic_account_id_to_group_name($test_result_row['AccountID']) . '</td><td>' . pts_strings::plural_handler(count(phoromatic_server::systems_associated_with_schedule($row['AccountID'], $row['ScheduleID'])), 'System') . '</td><td><strong>' . phoromatic_schedule_activeon_string($row['ActiveOn'], $row['RunAt']) . '</strong></td><td><a onclick="return confirm(\'Permanently remove this schedule?\');" href="/?admin_data/delete/schedule/' . $row['AccountID'] . '/' . $row['ScheduleID'] . '">Permanently Remove</a></td></tr></table></li></a>';
						}
						while($row = $result->fetchArray());
					}
		$main .= '</ul></div>';

		$main .= '<hr /><h1>Systems</h1>
			<h2>Active Systems</h2>
			<div class="pts_phoromatic_info_box_area">

					<ul>
						<li><h1>Active Systems</h1></li>';

					$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, LocalIP, CurrentTask, LastCommunication, EstimatedTimeForTask, TaskPercentComplete, AccountID FROM phoromatic_systems WHERE State >= 0 ORDER BY LastCommunication DESC');
					$result = $stmt->execute();
					$row = $result->fetchArray();
					$active_system_count = 0;

					if($row == false)
					{
						$main .= '<li class="light" style="text-align: center;">No Systems Found</li>';
					}
					else
					{
						do
						{
							$main .= '<a href="#"><li>' . $row['Title'] . '<br /><table><tr><td>' . phoromatic_account_id_to_group_name($row['AccountID']) . '</td><td>' . $row['LocalIP'] . '</td><td><strong>' . $row['CurrentTask'] . '</strong></td><td><strong>Last Communication:</strong> ' . date('j F Y H:i', strtotime($row['LastCommunication'])) . '</td><td><a onclick="return confirm(\'Permanently remove this system?\');" href="/?admin_data/delete/system/' . $row['AccountID'] . '/' . $row['SystemID'] . '">Permanently Remove</a></td></tr></table></li></a>';
							$active_system_count++;
						}
						while($row = $result->fetchArray());
					}


			$main .= '</ul></div>';
			$main .= '<h2>Inactive Systems</h2>
			<div class="pts_phoromatic_info_box_area">

					<ul>
						<li><h1>Active Systems</h1></li>';

					$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, LocalIP, CurrentTask, LastCommunication, EstimatedTimeForTask, TaskPercentComplete, AccountID FROM phoromatic_systems WHERE State < 0 ORDER BY LastCommunication DESC');
					$result = $stmt->execute();
					$row = $result->fetchArray();
					$active_system_count = 0;

					if($row == false)
					{
						$main .= '<li class="light" style="text-align: center;">No Systems Found</li>';
					}
					else
					{
						do
						{
							$main .= '<a href="#"><li>' . $row['Title'] . '<br /><table><tr><td>' . phoromatic_account_id_to_group_name($row['AccountID']) . '</td><td>' . $row['LocalIP'] . '</td><td><strong>' . $row['CurrentTask'] . '</strong></td><td><strong>Last Communication:</strong> ' . date('j F Y H:i', strtotime($row['LastCommunication'])) . '</td><td><a onclick="return confirm(\'Permanently remove this system?\');" href="/?admin_data/delete/system/' . $row['AccountID'] . '/' . $row['SystemID'] . '">Permanently Remove</a></td></tr></table></li></a>';
							$active_system_count++;
						}
						while($row = $result->fetchArray());
					}


			$main .= '</ul></div>';

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
