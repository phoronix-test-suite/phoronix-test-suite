<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2021, Phoronix Media
	Copyright (C) 2008 - 2021, Michael Larabel

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

class phoromatic_logs implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Logs';
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
		$main = null;
		if(isset($PATH[0]))
		{
			if($PATH[0] == 'context' && isset($PATH[1]))
			{
				$attribs = explode(',', $PATH[1]);
				$stmt = phoromatic_server::$db->prepare('SELECT UserContextStep, UserContextLog FROM phoromatic_system_context_logs WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND SystemID = :system_id AND TriggerID = :trigger_id ORDER BY UploadTime ASC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':system_id', $attribs[0]);
				$stmt->bindValue(':schedule_id', $attribs[1]);
				$stmt->bindValue(':trigger_id', base64_decode($attribs[2]));
				$result = $stmt->execute();
				while($row = $result->fetchArray())
				{
					$main .= '<h2>' . $row['UserContextStep'] . '</h2><p>' . str_replace(PHP_EOL, '<br />', $row['UserContextLog']) . '</p><hr />';
				}
			}

		}

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main, phoromatic_webui_right_panel_logged_in(null));
		echo phoromatic_webui_footer();
	}
}

?>
