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

				if($PATH[1] == 'edit')
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
				$info_table = array('Status:' => $row['CurrentTask'], 'State:' => $state, 'Phoronix Test Suite Client:' => $row['ClientVersion'], 'Last IP:' => $row['LastIP'], 'Last Communication:' => $row['LastCommunication'], 'Initial Creation:' => $row['CreatedOn'], 'System ID:' => $row['SystemID']);
				$main .= '<h2>System State</h2>' . pts_webui::r2d_array_to_table($info_table, 'auto');

				$main .= '<hr /><h2>System Components</h2><div style="float: left; width: 50%;">';
				$components = pts_result_file_analyzer::system_component_string_to_array($row['Hardware']);
				$main .= pts_webui::r2d_array_to_table($components) . '</div><div style="float: left; width: 50%;">';
				$components = pts_result_file_analyzer::system_component_string_to_array($row['Software']);
				$main .= pts_webui::r2d_array_to_table($components) . '</div>';
			}
		}


		if($main == null)
		{
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
							$main .= '<a href="?systems/' . $row['SystemID'] . '"><li>' . $row['Title'] . '<br /><em>' . $row['LocalIP'] . ' - ' . $row['CurrentTask'] . ' - Last Activity: ' . $row['LastCommunication'] . '</em></li></a>';
						}
						while($row = $result->fetchArray());
					}


			$main .= '</ul>
				</div>
			</div>

			<hr />
			<h2>System Groups</h2>
			<p>System groups make it very easy to organize multiple test systems for targeting by test schedules. You can always add/remove systems to groups, create new groups, and add systems to multiple groups.</p>


			'
			;
		}

		$right = '<ul><li>Active Systems</li>';

		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State > 0 ORDER BY Title ASC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$row = $result->fetchArray();

		if($row == false)
		{
			$right .= '<li align="center">No Systems Found</li>';
		}
		else
		{
			do
			{
				$right .= '<li><a href="?systems/' . $row['SystemID'] . '">' . $row['Title'] . '</a></li>';
			}
			while($row = $result->fetchArray());
		}
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in($right));
		echo phoromatic_webui_footer();
	}
}

?>
