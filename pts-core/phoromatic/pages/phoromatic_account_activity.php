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

class phoromatic_account_activity implements pts_webui_interface
{
	public static function page_title()
	{
		return 'Account Activity';
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
		$main = '<h1>Recent Account Activity</h1>';
		$stmt = phoromatic_server::$db->prepare('SELECT * FROM phoromatic_activity_stream WHERE AccountID = :account_id ORDER BY ActivityTime DESC');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$prev_date = null;

		if(empty($row))
		{
			$main .= '<p>No activity found.</p>';
		}
		else
		{
			do
			{
				if($prev_date != substr($row['ActivityTime'], 0, 10))
				{
					if($prev_date != null)
						$main .= '</p><hr />';

					$prev_date = substr($row['ActivityTime'], 0, 10);
					$new_date = strtotime($row['ActivityTime']);

					if(date('Y-m-d') == $prev_date)
					{
						$main .= '<h2>Today</h2>';
					}
					else if($new_date > (time() - (60 * 60 * 24 * 6)))
					{
						$main .= '<h2>' . date('l', $new_date) . '</h2>';
					}
					else
					{
						$main .= '<h2>' . date('j F Y', $new_date) . '</h2>';
					}
					$main .= '<p>';
				}

				$id_link_format = $row['ActivityEventID'];
				switch($row['ActivityEvent'])
				{
					case 'settings':
						$event_link_format = '<a href="?settings">settings</a>';
						break;
					case 'users':
						$event_link_format = '<a href="?users">a user</a>';
						break;
					case 'benchmark':
						$event_link_format = '<a href="?benchmark">benchmark</a>';

						$stmt1 = phoromatic_server::$db->prepare('SELECT Title FROM phoromatic_benchmark_tickets WHERE AccountID = :account_id AND TicketID = :ticket_id');
						$stmt1->bindValue(':account_id', $_SESSION['AccountID']);
						$stmt1->bindValue(':ticket_id', $row['ActivityEventID']);
						$result1 = $stmt1->execute();
						$row1 = $result1->fetchArray();
						$id_link_format = '<a href="?benchmark/' . $row['ActivityEventID'] . '">' . $row1['Title'] . '</a>';
						break;
					case 'schedule':
						$event_link_format = '<a href="?schedules">schedule</a>';

						$stmt1 = phoromatic_server::$db->prepare('SELECT Title FROM phoromatic_schedules WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
						$stmt1->bindValue(':account_id', $_SESSION['AccountID']);
						$stmt1->bindValue(':schedule_id', $row['ActivityEventID']);
						$result1 = $stmt1->execute();
						$row1 = $result1->fetchArray();
						$id_link_format = '<a href="?schedules/' . $row['ActivityEventID'] . '">' . $row1['Title'] . '</a>';
						break;
					case 'tests_for_schedule':
						$event_link_format = 'a test for a schedule';

						$stmt1 = phoromatic_server::$db->prepare('SELECT Title FROM phoromatic_schedules WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
						$stmt1->bindValue(':account_id', $_SESSION['AccountID']);
						$stmt1->bindValue(':schedule_id', $row['ActivityEventID']);
						$result1 = $stmt1->execute();
						$row1 = $result1->fetchArray();
						$id_link_format = '<a href="?schedules/' . $row['ActivityEventID'] . '">' . $row1['Title'] . '</a>';
						break;
					case 'groups':
						$event_link_format = '<a href="?systems#group_edit">a group</a>';
						break;
					case 'suite':
						$event_link_format = '<a href="build_suite">test suite</a>';
						$id_link_format = '<a href="?local_suites#' . $row['ActivityEventID'] . '">' . $row['ActivityEventID'] . '</a>';
						break;
					default:
						$event_link_format = $row['ActivityEvent'];
						break;
				}

				if($row['ActivityCreatorType'] == 'USER')
				{
					$main .= '<em>' . date('H:i', strtotime($row['ActivityTime'])) . '</em> &nbsp; <strong>' . $row['ActivityCreator'] . '</strong> <strong> ' . $row['ActivityEventType'] . '</strong> <strong>' . $event_link_format . '</strong>';

					if($id_link_format != null)
						$main .= ': ' . $id_link_format;

					$main .= '<br />' . PHP_EOL;
				}

				//$main .= '<p>' .  $row['ActivityCreator'] . ' ' . $row['ActivityCreatorType'] . ' ' . $row['ActivityEvent'] . ' ' . $row['ActivityEventID'] . ' ' . $row['ActivityEventType'] . '</p>';
			}
			while($row = $result->fetchArray());

			if($prev_date != null)
				$main .= '</p>';
		}

		echo phoromatic_webui_header_logged_in();
		echo phoromatic_webui_main($main);
		echo phoromatic_webui_footer();
	}
}

?>
