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


class phoromatic_overview implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Schedule &amp; Result Overview';
	}
	public static function page_header()
	{
		return null;
	}
	public static function preload($PAGE)
	{
		return true;
	}
	protected static function result_match($schedule_id, $system_id, $date)
	{
		$stmt = phoromatic_server::$db->prepare('SELECT UploadID FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND SystemID = :system_id AND UploadTime LIKE :upload_time LIMIT 1');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$stmt->bindValue(':schedule_id', $schedule_id);
		$stmt->bindValue(':system_id', $system_id);
		$stmt->bindValue(':upload_time', '%' . $date);
		$result = $stmt->execute();
		return $result && $row = $result->fetchArray() ? $row['UploadID'] : false;
	}
	public static function render_page_process($PATH)
	{
		echo phoromatic_webui_header_logged_in();

		$show_date = date('Y-m-d');
		$show_day_of_week = date('N') - 1;

		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_schedules WHERE AccountID = :account_id AND State = 1 AND (SELECT COUNT(*) FROM phoromatic_schedules_tests WHERE AccountID = :account_id AND ScheduleID = phoromatic_schedules.ScheduleID) > 0 AND ActiveOn LIKE :active_day ORDER BY RunAt ASC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$stmt->bindValue(':active_day', '%' . $show_day_of_week . '%');
		$result = $stmt->execute();

		echo '<div style="margin: 10px 0 30px; clear: both; padding-bottom: 40px; display: block;">';

		while($row = $result->fetchArray())
		{
			list($h, $m) = explode('.', $row['RunAt']);
			$offset = (($h * 60) + $m) / 1440 * 100;

			echo '<div style="margin-left: ' . $offset . '%;" class="phoromatic_overview_box">';
			echo '<h1><a href="?schedules/' . $row['ScheduleID'] . '">' . $row['Title'] . '</a></h1>';

			foreach(phoromatic_server::systems_associated_with_schedule($_SESSION['AccountID'], $row['ScheduleID']) as $system_id)
			{
				$upload_id = self::result_match($row['ScheduleID'], $system_id, $show_date);

				if($upload_id)
					echo '<a href="?result/' . $upload_id . '">';

				echo phoromatic_server::system_id_to_name($system_id);

				if($upload_id)
					echo '</a>';

				echo '<br />';
			}

			echo '</div>';
		}

		echo '</div>';
		echo phoromatic_webui_footer();
	}
}

?>
