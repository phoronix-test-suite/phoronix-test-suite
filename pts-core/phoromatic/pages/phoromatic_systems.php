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


class phoromatic_systems implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Main';
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

		if(!empty($PATH[0]) && isset($_POST['system_title']) && !empty($_POST['system_title']) && isset($_POST['system_description']) && isset($_POST['system_state']))
		{
			$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET Title = :title, Description = :description, State = :state, CurrentTask = \'Awaiting Task\' WHERE AccountID = :account_id AND SystemID = :system_id');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':system_id', $PATH[0]);
			$stmt->bindValue(':title', $_POST['system_title']);
			$stmt->bindValue(':description', $_POST['system_description']);
			$stmt->bindValue(':state', $_POST['system_state']);
			$stmt->execute();
		}

		if(!empty($PATH[0]))
		{
			$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id ORDER BY LastCommunication DESC');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':system_id', $PATH[0]);
			$result = $stmt->execute();

			if(!empty($result))
			{
				$row = $result->fetchArray();

				if(isset($PATH[1]) && $PATH[1] == 'edit')
				{
					$main = '<h1>' . $row['Title'] . '</h1>';
					$main .= '<form name="system_form" id="system_form" action="?systems/' . $PATH[0] . '" method="post" onsubmit="return phoromatic_system_edit(this);">
			<p><div style="width: 200px; font-weight: bold; float: left;">System Title:</div> <input type="text" style="width: 400px;" name="system_title" value="' . $row['Title'] . '" /></p>
			<p><div style="width: 200px; font-weight: bold; float: left;">System Description:</div> <textarea style="width: 400px;" name="system_description">' . $row['Description'] . '</textarea></p>
			<p><div style="width: 200px; font-weight: bold; float: left;">System State:</div><select name="system_state" style="width: 200px;"><option value="-1">Disabled</option><option value="1" selected="selected">Enabled</option></select></p>
			<p><div style="width: 200px; font-weight: bold; float: left;">&nbsp;</div> <input type="submit" value="Submit" /></p></form>';
				}
				else
				{
					$main = '<h1>' . $row['Title'] . '</h1><p><em>' . ($row['Description'] != null ? $row['Description'] : 'No system description.') . '</em></p>';
					$main .= '<p><a href="?systems/' . $PATH[0] . '/edit">Edit Task & Enable/Disable System</a></p>';
				}

				switch($row['State'])
				{
					case -1:
						$state = 'Disabled';
						break;
					case 0:
						$state = 'Connected; Awaiting Approval';
						break;
					case 1:
						$state = 'Active';
						break;
				}

				$main .= '<hr />';
				$info_table = array('Status:' => $row['CurrentTask'], 'State:' => $state, 'Phoronix Test Suite Client:' => $row['ClientVersion'], 'Last IP:' => $row['LastIP'], 'Initial Creation:' => phoromatic_user_friendly_timedate($row['CreatedOn']), 'Last Communication:' => phoromatic_user_friendly_timedate($row['LastCommunication']), 'System ID:' => $row['SystemID']);
				$main .= '<h2>System State</h2>' . pts_webui::r2d_array_to_table($info_table, 'auto');

				$main .= '<hr /><h2>System Components</h2><div style="float: left; width: 50%;">';
				$components = pts_result_file_analyzer::system_component_string_to_array($row['Hardware']);
				$main .= pts_webui::r2d_array_to_table($components) . '</div><div style="float: left; width: 50%;">';
				$components = pts_result_file_analyzer::system_component_string_to_array($row['Software']);
				$main .= pts_webui::r2d_array_to_table($components) . '</div>';

				$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, ScheduleID, UploadID, UploadTime FROM phoromatic_results WHERE AccountID = :account_id AND SystemID = :system_id ORDER BY UploadTime DESC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':system_id', $PATH[0]);
				$test_result_result = $stmt->execute();
				$test_result_row = $test_result_result->fetchArray();
				$results = 0;

				if($test_result_row != false)
				{
					$main .= '<h2>Test Results</h2>';
					$main .= '<div class="pts_phoromatic_info_box_area">';
					$main .= '<div style="float: left; width: 100%;"><ul><li><h1>Recent Test Results</h1></li>';

					do
					{
						if($results > 20)
						{
							break;
						}

						$main .= '<a href="?results/' . $test_result_row['UploadID'] . '"><li>' . $test_result_row['Title'] . '<br /><span style="color: #065695;"><em>' . phoromatic_system_id_to_name($test_result_row['SystemID']) . ' - ' . phoromatic_user_friendly_timedate($test_result_row['UploadTime']) .  '</em></span></li></a>';
						$results++;

					}
					while($test_result_row = $test_result_result->fetchArray());
				}

				if($results > 0)
				{
					$main .= '</ul></div>';
				}


			}
		}


		if($main == null)
		{
			if(isset($_POST['new_group']) && !empty($_POST['new_group']))
			{
				$group = trim($_POST['new_group']);

				if($group)
				{
					$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_groups (AccountID, GroupName) VALUES (:account_id, :group_name)');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$stmt->bindValue(':group_name', $group);
					$result = $stmt->execute();

					if(!empty($_POST['systems_for_group']) && is_array($_POST['systems_for_group']))
					{
						foreach($_POST['systems_for_group'] as $sid)
						{
							$stmt = phoromatic_server::$db->prepare('UPDATE phoromatic_systems SET Groups = Groups || :new_group WHERE AccountID = :account_id AND SystemID = :system_id');
							$stmt->bindValue(':account_id', $_SESSION['AccountID']);
							$stmt->bindValue(':system_id', $sid);
							$stmt->bindValue(':new_group', '#' . $group . '#');
							$stmt->execute();
						}
					}
				}
			}

			$main = '<h1>Test Systems</h1>';
			$main .= phoromatic_systems_needing_attention();
			$main .= '<h2>Add A System</h2>
				<p>To connect a <a href="http://www.phoronix-test-suite.com/">Phoronix Test Suite</a> test system to this account for remotely managing and/or carrying out routine automated benchmarking, follow these simple and quick steps:</p>
				<ol><li>From a system with <em>Phoronix Test Suite 5.2 or newer</em> run <strong>phoronix-test-suite phoromatic.connect ' . phoromatic_web_socket_server_addr() . '</strong>. (The test system must be able to access this server\'s correct IP address / domain name.)</li><li>When you have run the command from the test system, you will need to log into this page on Phoromatic server again where you can approve the system and configure the system settings so you can begin using it as part of this Phoromatic account.</li><li>Repeat the two steps for as many systems as you would like! When you are all done -- if you haven\'t done so already, you can start creating test schedules, groups, and other Phoromatic events.</li></ol>


				<hr />

			<h2>Systems</h2>
			<div class="pts_phoromatic_info_box_area">

				<div style="float: left; width: 100%;">
					<ul>
						<li><h1>Active Systems</h1></li>';

					$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, LocalIP, CurrentTask, LastCommunication FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY LastCommunication DESC');
					$stmt->bindValue(':account_id', $_SESSION['AccountID']);
					$result = $stmt->execute();
					$row = $result->fetchArray();

					if($row == false)
					{
						$main .= '<li class="light" style="text-align: center;">No Systems Found</li>';
					}
					else
					{
						do
						{
							$main .= '<a href="?systems/' . $row['SystemID'] . '"><li>' . $row['Title'] . '<br /><span style="color: #065695;"><em>' . $row['LocalIP'] . ' - ' . $row['CurrentTask'] . ' - Last Activity: ' . $row['LastCommunication'] . '</em></span></li></a>';
						}
						while($row = $result->fetchArray());
					}


			$main .= '</ul>
				</div>
			</div>

			<hr />
			<h2>System Groups</h2>
			<p>System groups make it very easy to organize multiple test systems for targeting by test schedules. You can always add/remove systems to groups, create new groups, and add systems to multiple groups. After creating a group and adding systems to the group, you can begin targeting tests against a particular group of systems. Systems can always be added/removed from groups later and a system can belong to multiple groups.</p>';


			$main .= '<div style="float: left;"><form name="new_group_form" id="new_group_form" action="?systems" method="post" onsubmit="return phoromatic_new_group(this);">
			<p><div style="width: 200px; font-weight: bold; float: left;">New Group Name:</div> <input type="text" style="width: 300px;" name="new_group" value="" /></p>
			<p><div style="width: 200px; font-weight: bold; float: left;">Select System(s) To Add To Group:</div><select name="systems_for_group[]" multiple="multiple" style="width: 300px;">';

			$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY Title ASC');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$result = $stmt->execute();
			$row = $result->fetchArray();

			if($row != false)
			{
				do
				{
					$main .= '<option value="' . $row['SystemID'] . '">' . $row['Title'] . '</option>';
				}
				while($row = $result->fetchArray());
			}


			$main .= '</select></p>
			<p><div style="width: 200px; font-weight: bold; float: left;">&nbsp;</div> <input type="submit" value="Create Group" /></p></form></div>';

			$stmt = phoromatic_server::$db->prepare('SELECT GroupName FROM phoromatic_groups WHERE AccountID = :account_id ORDER BY GroupName ASC');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$result = $stmt->execute();
			$row = $result->fetchArray();

			if($row != false)
			{
				$main .= '<div style="float: left; margin-left: 90px;"><h3>Current System Groups</h3>';

				do
				{
					$stmt_count = phoromatic_server::$db->prepare('SELECT COUNT(SystemID) AS system_count FROM phoromatic_systems WHERE AccountID = :account_id AND Groups LIKE \'%#' . $row['GroupName'] . '#%\'');
					$stmt_count->bindValue(':account_id', $_SESSION['AccountID']);
					$result_count = $stmt_count->execute();
					$row_count = $result_count->fetchArray();
					$row_count['system_count'] = isset($row_count['system_count']) ? $row_count['system_count'] : 0;

					$main .= '<p><div style="width: 200px; float: left; font-weight: bold;">' . $row['GroupName'] . '</div> ' . $row_count['system_count'] . ' System' . ($row_count['system_count'] != 1 ? 's' : '') . '</p>';

				}
				while($row = $result->fetchArray());

				$main .= '</div>';
			}
		}

		$right = '<ul><li>Active Systems</li>';

		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State > 0 ORDER BY Title ASC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$row = $result->fetchArray();

		if($row == false)
		{
			$right .= '</ul><p style="text-align: left; margin: 6px 10px;">No Systems Found</p>';
		}
		else
		{
			do
			{
				$right .= '<li><a href="?systems/' . $row['SystemID'] . '">' . $row['Title'] . '</a></li>';
			}
			while($row = $result->fetchArray());
			$right .= '</ul>';
		}
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in($right));
		echo phoromatic_webui_footer();
	}
}

?>
