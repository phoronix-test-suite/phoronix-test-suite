<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2016, Phoronix Media
	Copyright (C) 2015 - 2016, Michael Larabel

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

class phoromatic_maintenance_table implements pts_webui_interface
{
	public static function page_title()
	{
		return 'System Maintenance Table';
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

		$main = '<h1>Systems</h1>';
		$main .= '<p>Various system interaction vitals for the Phoronix Test Suite systems associated with this account.</p>';
		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, Hardware, Software, ClientVersion, LastIP, NetworkMAC, LastCommunication, CurrentTask, CoreVersion, NetworkWakeOnLAN, BlockPowerOffs FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY LastCommunication DESC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();

		while($row = $result->fetchArray())
		{
			$stmt = phoromatic_server::$db->prepare('SELECT UploadTime FROM phoromatic_results WHERE AccountID = :account_id AND SystemID = :system_id ORDER BY UploadTime DESC LIMIT 1');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
			$stmt->bindValue(':system_id', $row['SystemID']);
			$latest_result = $stmt->execute();
			$latest_result = $latest_result->fetchArray();
			$latest_result = $latest_result['UploadTime'];

			$components[$row['SystemID']]['Last Communication'] = date('H:i d F', strtotime($row['LastCommunication']));
			$components[$row['SystemID']]['Current Task'] = $row['CurrentTask'];
			$components[$row['SystemID']]['Last IP'] = $row['LastIP'];
			$components[$row['SystemID']]['Phoronix Test Suite'] = $row['ClientVersion'] . ' [' . $row['CoreVersion'] . ']';
			$components[$row['SystemID']]['MAC'] = $row['NetworkMAC'];
			$components[$row['SystemID']]['Wake-On-LAN'] = (empty($row['NetworkWakeOnLAN']) ? 'N/A' : $row['NetworkWakeOnLAN']) . ' - ' . ($row['BlockPowerOffs'] == 1 ? 'Blocked' : 'Permitted');
			$components[$row['SystemID']]['Latest Result Upload'] = $latest_result != null ? date('d F', strtotime($latest_result)) : 'N/A';
			$system_ids[$row['SystemID']] = $row['Title'];
		}

		$main .= '<div style="margin: 10px auto; overflow: auto;"><table width="100%">';
		$component_types = array('Last Communication', 'Current Task', 'Phoronix Test Suite', 'Last IP', 'MAC', 'Wake-On-LAN', 'Latest Result Upload');
		$main .= '<tr><th>&nbsp;</th>';
		foreach($component_types as $type)
		{
			$main .= '<th>' . $type . '</th>';
		}
		foreach($components as $system_id => $component_array)
		{
			$main .= '<tr>';
			$main .= '<th><a href="/?systems/' . $system_id . '">' . $system_ids[$system_id] . '</a></th>';
			foreach($component_types as $type)
			{
				$c = (isset($component_array[$type]) ? $component_array[$type] : 'N/A');
				if(($x = stripos($c, ' @ ')) !== false)
				{
					$c = substr($c, 0, $x);
				}
				if(($x = stripos($c, ' (')) !== false)
				{
					$c = substr($c, 0, $x);
				}

				$main .= '<td>' . $c . '</td>';
			}
			$main .= '</tr>';
		}
		$main .= '</table></div>';

		$right = null;
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in($right));
		echo phoromatic_webui_footer();
	}
}

?>
