<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014, Phoronix Media
	Copyright (C) 2014, Michael Larabel

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

class phoromatic_component_table implements pts_webui_interface
{
	public static function page_title()
	{
		return 'System Component Table';
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

		$main = '<h1>System Components</h1>';
		$main .= '<p>Detected hardware/software components via Phoronix Test Suite\'s Phodevi implementation on the Phoromatic client systems.</p>';
		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, Hardware, Software, ClientVersion, NetworkWakeOnLAN, NetworkMAC FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY Title ASC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();

		while($row = $result->fetchArray())
		{
			$components[$row['SystemID']] = array_merge(pts_result_file_analyzer::system_component_string_to_array($row['Software'], array('OS', 'Kernel', 'OpenGL', 'File-System')), pts_result_file_analyzer::system_component_string_to_array($row['Hardware'], array('Processor', 'Motherboard', 'Memory', 'Disk', 'Graphics')));
			$components[$row['SystemID']]['Phoronix Test Suite'] = $row['ClientVersion'];
			$components[$row['SystemID']]['WoL Info'] = $row['NetworkWakeOnLAN'];
			$components[$row['SystemID']]['MAC'] = $row['NetworkMAC'];
			$system_ids[$row['SystemID']] = $row['Title'];
		}

		$main .= '<div style="margin: 10px auto; overflow: auto;"><table>';
		$component_types = array('MAC', 'Processor', 'Motherboard', 'Memory', 'Disk', 'Graphics', 'OS', 'Kernel', 'OpenGL', 'File-System', 'Phoronix Test Suite', 'WoL Info');
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
