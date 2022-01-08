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
						$pprids = explode(',', $PATH[2]);

						foreach($pprids as $pprid)
						{
							$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_results WHERE PPRID = :pprid LIMIT 1');
							$stmt->bindValue(':pprid', $pprid);
							$result = $stmt->execute();
							if($result && ($row = $result->fetchArray()))
							{
								$composite_xml = phoromatic_server::phoromatic_account_result_path($row['AccountID'], $row['UploadID']) . 'composite.xml';
								if(is_file($composite_xml))
								{
									unlink($composite_xml);
								}

								pts_file_io::delete(phoromatic_server::phoromatic_account_result_path($row['AccountID'], $row['UploadID']), null, true);

								$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_results_results WHERE AccountID = :account_id AND UploadID = :upload_id');
								$stmt->bindValue(':account_id', $row['AccountID']);
								$stmt->bindValue(':upload_id', $row['UploadID']);
								$result = $stmt->execute();

								$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_results_systems WHERE AccountID = :account_id AND UploadID = :upload_id');
								$stmt->bindValue(':account_id', $row['AccountID']);
								$stmt->bindValue(':upload_id', $row['UploadID']);
								$result = $stmt->execute();
							}

							$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_results WHERE PPRID = :pprid');
							$stmt->bindValue(':pprid', $pprid);
							$result = $stmt->execute();

							// TODO XXX fix below
							//$upload_dir = phoromatic_server::phoromatic_account_result_path($_SESSION['AccountID'], $upload_id);
							//pts_file_io::delete($upload_dir);
						}

/*						$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_results_results WHERE AccountID = :account_id AND UploadID = :upload_id');
						$stmt->bindValue(':account_id', $PATH[2]);
						$stmt->bindValue(':upload_id', $PATH[3]);
						$result = $stmt->execute();
						$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_results_systems WHERE AccountID = :account_id AND UploadID = :upload_id');
						$stmt->bindValue(':account_id', $PATH[2]);
						$stmt->bindValue(':upload_id', $PATH[3]);
						$result = $stmt->execute();

						$result_dir = phoromatic_server::phoromatic_account_result_path($PATH[2], $PATH[3]);
						if(is_dir($result_dir))
						{
							pts_file_io::delete($result_dir, null, true);
						}
*/
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
					else if($PATH[1] == 'ticket')
					{
						$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_benchmark_tickets WHERE AccountID = :account_id AND TicketID = :ticket_id');
						$stmt->bindValue(':account_id', $PATH[2]);
						$stmt->bindValue(':ticket_id', $PATH[3]);
						$result = $stmt->execute();
					}
					else if($PATH[1] == 'trigger')
					{
						$stmt = phoromatic_server::$db->prepare('DELETE FROM phoromatic_schedules_triggers WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND Trigger = :trigger');
						$stmt->bindValue(':account_id', $PATH[2]);
						$stmt->bindValue(':schedule_id', $PATH[3]);
						$stmt->bindValue(':trigger', $PATH[4]);
						$result = $stmt->execute(); var_dump($result);
					}
					break;
			}
		}

		$main = '<h1>Phoromatic Server Data</h1>';
		$main .= '<h1>Test Results</h1>';
	$main .= '<a onclick="javascript:phoromatic_generate_comparison(\'public.php?ut=\');"><div id="phoromatic_result_compare_info_box" style="background: #1976d2; border: 1px solid #000;"></div></a> <a onclick="javascript:phoromatic_delete_results(\'?admin_data/delete/result/\'); return false;"><div id="phoromatic_result_delete_box" style="background: #1976d2; border: 1px solid #000;">Delete Selected Results</div></a>';
		$main .= '<div class="pts_phoromatic_info_box_area">';
		$main .= '<div style="height: 500px;"><ul style="max-height: 100%;"><li><h1>Recent Test Results</h1></li>';
		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, PPRID, UploadTime, TimesViewed, AccountID, UploadID FROM phoromatic_results ORDER BY UploadTime DESC LIMIT 100');
		$test_result_result = $stmt->execute();
		$results = 0;
		while($test_result_row = $test_result_result->fetchArray())
		{
			$main .= '<a onclick=""><li id="result_select_' . $test_result_row['PPRID'] . '"><input type="checkbox" id="result_compare_checkbox_' . $test_result_row['PPRID'] . '" onclick="javascript:phoromatic_checkbox_toggle_result_comparison(\'' . $test_result_row['PPRID'] . '\');" onchange="return false;"></input> <span onclick="javascript:phoromatic_window_redirect(\'public.php?ut=' . $test_result_row['PPRID'] . '\');">' . $test_result_row['Title'] . '</span><br /><table><tr><td>' . phoromatic_server::system_id_to_name($test_result_row['SystemID'], $test_result_row['AccountID']) . '</td><td>' . phoromatic_server::user_friendly_timedate($test_result_row['UploadTime']) .  '</td><td>' . $test_result_row['TimesViewed'] . ' Times Viewed</td></table></li></a>';
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

							$main .= '<a onclick=""><li>' . $row['Title'] . '<br /><table><tr><td>' . phoromatic_server::account_id_to_group_name($row['AccountID']) . '</td><td>' . pts_strings::plural_handler(count(phoromatic_server::systems_associated_with_schedule($row['AccountID'], $row['ScheduleID'])), 'System') . '</td><td><strong>' . phoromatic_schedule_activeon_string($row['ActiveOn'], $row['RunAt']) . '</strong></td><td><a onclick="return confirm(\'Permanently remove this schedule?\');" href="/?admin_data/delete/schedule/' . $row['AccountID'] . '/' . $row['ScheduleID'] . '">Permanently Remove</a></td></tr></table></li></a>';
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

							$main .= '<a onclick=""><li>' . $row['Title'] . '<br /><table><tr><td>' . phoromatic_server::account_id_to_group_name($row['AccountID']) . '</td><td>' . pts_strings::plural_handler(count(phoromatic_server::systems_associated_with_schedule($row['AccountID'], $row['ScheduleID'])), 'System') . '</td><td><strong>' . phoromatic_schedule_activeon_string($row['ActiveOn'], $row['RunAt']) . '</strong></td><td><a onclick="return confirm(\'Permanently remove this schedule?\');" href="/?admin_data/delete/schedule/' . $row['AccountID'] . '/' . $row['ScheduleID'] . '">Permanently Remove</a></td></tr></table></li></a>';
						}
						while($row = $result->fetchArray());
					}
		$main .= '</ul></div>';

		$main .= '<hr /><h2>Schedule Triggers</h2>';
		$main .= '<div class="pts_phoromatic_info_box_area">
				<ul>
					<li><h1>Triggers</h1></li>';
					$stmt = phoromatic_server::$db->prepare('SELECT Trigger, TriggeredOn, AccountID, ScheduleID FROM phoromatic_schedules_triggers ORDER BY TriggeredOn DESC');
					$result = $stmt->execute();
					$row = $result->fetchArray();

					if($row == false)
					{
						$main .= '<li class="light" style="text-align: center;">No Triggers Found</li>';
					}
					else
					{
						do
						{

							$main .= '<a onclick=""><li>' . $row['Trigger'] . '<br /><table><tr><td>' . $row['TriggeredOn'] . '</td><td>' . phoromatic_server::account_id_to_group_name($row['AccountID']) . '</td><td><a onclick="return confirm(\'Permanently remove this trigger?\');" href="/?admin_data/delete/trigger/' . $row['AccountID'] . '/' . $row['ScheduleID'] . '/' . $row['Trigger'] . '">Permanently Remove</a></td></tr></table></li></a>';
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
							$main .= '<a onclick=""><li>' . $row['Title'] . '<br /><table><tr><td>' . phoromatic_server::account_id_to_group_name($row['AccountID']) . '</td><td>' . $row['LocalIP'] . '</td><td><strong>' . $row['CurrentTask'] . '</strong></td><td><strong>Last Communication:</strong> ' . date('j F Y H:i', strtotime($row['LastCommunication'])) . '</td><td><a onclick="return confirm(\'Permanently remove this system?\');" href="/?admin_data/delete/system/' . $row['AccountID'] . '/' . $row['SystemID'] . '">Permanently Remove</a></td></tr></table></li></a>';
							$active_system_count++;
						}
						while($row = $result->fetchArray());
					}


			$main .= '</ul></div>';
			$main .= '<h2>Inactive Systems</h2>
			<div class="pts_phoromatic_info_box_area">
					<ul>
						<li><h1>Inactive Systems</h1></li>';

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
							$main .= '<a onclick=""><li>' . $row['Title'] . '<br /><table><tr><td>' . phoromatic_server::account_id_to_group_name($row['AccountID']) . '</td><td>' . $row['LocalIP'] . '</td><td><strong>' . $row['CurrentTask'] . '</strong></td><td><strong>Last Communication:</strong> ' . date('j F Y H:i', strtotime($row['LastCommunication'])) . '</td><td><a onclick="return confirm(\'Permanently remove this system?\');" href="/?admin_data/delete/system/' . $row['AccountID'] . '/' . $row['SystemID'] . '">Permanently Remove</a></td></tr></table></li></a>';
							$active_system_count++;
						}
						while($row = $result->fetchArray());
					}


			$main .= '</ul></div>';

		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_benchmark_tickets ORDER BY TicketIssueTime DESC');
		$result = $stmt->execute();

		$main .= '<hr /><h1>Benchmark Tickets</h1>
			<div class="pts_phoromatic_info_box_area"><ul><li><h1>Tickets</h1></li>';
		while($result && $row = $result->fetchArray())
		{
				$main .= '<a onclick=""><li>' . $row['Title'] . '<br /><table><tr><td><a onclick="return confirm(\'Permanently remove this system?\');" href="/?admin_data/delete/ticket/' . $row['AccountID'] . '/' . $row['TicketID'] . '">Permanently Remove</a></td></tr></table></li></a>';
		}
		$main .= '</ul></div>';

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in());
		echo phoromatic_webui_footer();
	}
}

?>
