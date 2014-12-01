<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2014, Phoronix Media
	Copyright (C) 2013 - 2014, Michael Larabel

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

define('PHOROMATIC_USER_IS_VIEWER', !isset($_SESSION['AdminLevel']) || $_SESSION['AdminLevel'] >= 10 || $_SESSION['AdminLevel'] < 1 ? true : false);

function phoromatic_user_friendly_timedate($time)
{
	return phoromatic_server::user_friendly_timedate($time);
}
function phoromatic_compute_estimated_time_remaining_string($estimated_minutes, $last_comm, $append = 'Remaining')
{
	$remaining = phoromatic_compute_estimated_time_remaining($estimated_minutes, $last_comm);
	return $remaining > 0 ? '~' . pts_strings::plural_handler($remaining, 'Minute') . ' ' . $append : ' ';
}
function phoromatic_compute_estimated_time_remaining($estimated_minutes, $last_comm)
{
	if($estimated_minutes > 0)
	{
		$estimated_completion = strtotime($last_comm) + ($estimated_minutes * 60);

		if(time() < $estimated_completion)
		{
			return ceil(($estimated_completion - time()) / 60);
		}

	}

	return 0;
}
function phoromatic_webui_header($left_items, $right)
{
	$ret = '<div id="pts_phoromatic_top_header">
	<div id="pts_phoromatic_logo"><a href="?"><img src="data:image/png;base64,' . base64_encode(file_get_contents('images/phoromatic_logo.png')) . '" /></a></div><ul>';

	foreach($left_items as $item)
	{
		$ret .= '<li>' . $item . '</li>';
	}
	$ret .= '</ul><div style="float: right; padding: 25px 70px 0 0;">' . $right .'</div></div>';

	return $ret;
}
function phoromatic_get_posted_var($name, $default_value = null)
{
	return isset($_POST[$name]) ? $_POST[$name] : null;
}
function phoromatic_webui_main($main, $right)
{
	return '<div id="pts_phoromatic_main"><div id="pts_phoromatic_menu_right">' . $right . '</div><div id="pts_phoromatic_main_area">' . $main . '</div><div style="clear: both;"></div></div>';
}
function phoromatic_webui_box(&$box)
{
	return '<div id="pts_phoromatic_main_box"><div id="pts_phoromatic_main_box_inside">' . $box . '</div></div>';
}
function phoromatic_results_for_schedule($schedule_id, $limit_results = false)
{
	switch($limit_results)
	{
		case 'TODAY':
			$stmt = phoromatic_server::$db->prepare('SELECT COUNT(UploadID) As UploadCount FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id AND UploadTime LIKE :today_date');
			$stmt->bindValue(':today_date', date('Y-m-d') . '%');
			break;
		default:
			$stmt = phoromatic_server::$db->prepare('SELECT COUNT(UploadID) As UploadCount FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
			break;
	}

	$stmt->bindValue(':account_id', $_SESSION['AccountID']);
	$stmt->bindValue(':schedule_id', $schedule_id);
	$test_result_result = $stmt->execute();
	$row = $test_result_result->fetchArray();

	return empty($row) ? 0 : $row['UploadCount'];
}
function phoromatic_schedule_activeon_string($active_on, $active_at = null)
{
	if(!empty($active_on))
	{
		$active_days = explode(',', $active_on);
		$week = array('M', 'T', 'W', 'TH', 'F', 'S', 'SU');
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
		return implode(' ', $active_days) . (!empty($active_at) ? ' @ ' . str_replace('.', ':', $active_at) : null );
	}
}
function phoromatic_webui_footer()
{
	return '<div id="pts_phoromatic_bottom_footer">
<div style="float: right; padding: 2px 10px; overflow: hidden;"><a href="http://openbenchmarking.org/" style="margin-right: 20px;"><img src="data:image/png;base64,' . base64_encode(file_get_contents('images/ob-white-logo.png')) . '" /></a> <a href="http://www.phoronix-test-suite.com/"><img src="data:image/png;base64,' . base64_encode(file_get_contents('images/pts-white-logo.png')) . '" /></a></div>
<p style="margin: 6px 15px;">Copyright &copy; 2008 - ' . date('Y') . ' by <a href="http://www.phoronix-media.com/">Phoronix Media</a>. All rights reserved.<br />
All trademarks used are properties of their respective owners.<br />' . pts_title(true) . ' - Core Version ' . PTS_CORE_VERSION . ' - PHP ' . PHP_VERSION . '</p></div>';
}
function phoromatic_add_activity_stream_event($activity_event, $activity_event_id, $activity_event_type)
{
	$stmt = phoromatic_server::$db->prepare('INSERT INTO phoromatic_activity_stream (AccountID, ActivityTime, ActivityCreator, ActivityCreatorType, ActivityEvent, ActivityEventID, ActivityEventType) VALUES (:account_id, :activity_time, :activity_creator, :activity_creator_type, :activity_event, :activity_event_id, :activity_event_type)');
	$stmt->bindValue(':account_id', $_SESSION['AccountID']);
	$stmt->bindValue(':activity_time', phoromatic_server::current_time());
	$stmt->bindValue(':activity_creator', $_SESSION['UserName']);
	$stmt->bindValue(':activity_creator_type', 'USER');
	$stmt->bindValue(':activity_event', $activity_event);
	$stmt->bindValue(':activity_event_id', $activity_event_id);
	$stmt->bindValue(':activity_event_type', $activity_event_type);
	return $stmt->execute();
}
function phoromatic_webui_header_logged_in()
{
	$html_links = array();
	if($_SESSION['AdminLevel'] == -40)
	{
		$pages = array('Admin', 'Admin_Config');
	}
	else if($_SESSION['AdminLevel'] > 0)
	{
		$pages = array('Main', 'Systems', 'Settings', 'Schedules', 'Results');

		if(isset($_SESSION['AdminLevel']) && $_SESSION['AdminLevel'] < 4)
		{
			array_push($pages, 'Users');
		}
	}

	foreach($pages as $page)
	{
		if(strtolower($page) == PAGE_REQUEST)
		{
			array_push($html_links, '<a href="?' . strtolower($page) . '"><u>' . str_replace('_', ' ', $page) . '</u></a>');
		}
		else
		{
			array_push($html_links, '<a href="?' . strtolower($page) . '">' . str_replace('_', ' ', $page) . '</a>');
		}
	}

	return phoromatic_webui_header($html_links, '<form action="#" id="search"><input type="search" name="q" size="14" disabled="disabled" /><input type="submit" name="sa" value="Search" disabled="disabled" /></form>');
}
function phoromatic_webui_right_panel_logged_in($add = null)
{
	$right = null;
	if($_SESSION['AdminLevel'] == -40)
	{
		$right .= '<h3>Phoromatic Server</h3><hr /><p><strong>' . date('H:i T - j F Y') . '</p><p align="center"><a href="?logout"><strong>Log-Out</strong></a></p>';
	}
	else if($_SESSION['AdminLevel'] > 0)
	{
		if(($bad_systems = phoromatic_server::systems_appearing_down()) != false)
		{
			$right .= '<ul><li><span class="alert">Systems Needing Attention</span></li>';
			foreach($bad_systems as $system)
			{
				$right .= '<li><a href="?systems/' . $system . '">' . phoromatic_server::system_id_to_name($system) . '</a></li>';
			}
			$right .= '</ul><hr />';
		}

		$right .= $add;

		if($add == null)
		{
			$recently_active_systems = phoromatic_server::recently_active_systems($_SESSION['AccountID']);
			if(!empty($recently_active_systems))
			{
				$right .= '<ul><li>Recently Active Systems</li>';

				foreach($recently_active_systems as &$row)
				{
					$right .= '<li><a href="?systems/' . $row['SystemID'] . '">' . $row['Title'] . '</a></li>';
				}

				$right .= '</ul><hr />';
			}

			$right .= '
				<ul>
					<li>Today\'s Scheduled Events</li>';

				$stmt = phoromatic_server::$db->prepare('SELECT Title, ScheduleID, RunAt FROM phoromatic_schedules WHERE AccountID = :account_id AND State >= 1  AND ActiveOn LIKE :active_on ORDER BY RunAt,Title ASC');
				$stmt->bindValue(':account_id', $_SESSION['AccountID']);
				$stmt->bindValue(':active_on', '%' . (date('N') - 1) . '%');
				$result = $stmt->execute();
				$row = $result->fetchArray();

				if($row == false)
				{
					$right .= '</ul><p style="text-align: left; margin: 6px 10px;">No Events Found</p>';
				}
				else
				{
					do
					{
						$right .= '<li>' . $row['RunAt'] . ' <a href="?schedules/' . $row['ScheduleID'] . '">' . $row['Title'] . '</a></li>';
					}
					while($row = $result->fetchArray());
					$right .= '</ul>';
				}

		}

		$stmt = phoromatic_server::$db->prepare('SELECT COUNT(Title) AS SystemCount FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$system_count = $row['SystemCount'];
		$stmt = phoromatic_server::$db->prepare('SELECT COUNT(Title) AS ScheduleCount FROM phoromatic_schedules WHERE AccountID = :account_id AND State >= 1');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$schedule_count = $row['ScheduleCount'];
		$stmt = phoromatic_server::$db->prepare('SELECT COUNT(UploadID) AS ResultCount FROM phoromatic_results WHERE AccountID = :account_id');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$result_count = $row['ResultCount'];
		$stmt = phoromatic_server::$db->prepare('SELECT COUNT(ActivityTime) AS ActivityCount FROM phoromatic_activity_stream WHERE AccountID = :account_id AND ActivityTime LIKE :today_date');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$stmt->bindValue(':today_date', date('Y-m-d') . '%');
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$activity_count = $row['ActivityCount'];
		$right .= '<hr /><p><strong>' . date('H:i T - j F Y') . '</strong><br /><a href="?systems">' . $system_count . ' System' . ($system_count == 1 ? '' : 's') . '</a><br /><a href="?schedules">' . $schedule_count . ' Schedule' . ($schedule_count == 1 ? '' : 's') . '</a><br /><a href="?results">' . $result_count . ' Result' . ($result_count == 1 ? '' : 's') . '</a><br /><a href="?account_activity">' . $activity_count . ' Activity Events Today</a><br /><a href="?logout"><strong>Log-Out</strong></a></p>';
	}

	return $right;
}
function phoromatic_web_socket_server_ip()
{
	$server_ip = $_SERVER['HTTP_HOST'];
	if(($x = strpos($server_ip, ':')) !== false)
	{
		$server_ip = substr($server_ip, 0, $x);
	}

	if($server_ip == 'localhost' || $server_ip == '0.0.0.0')
	{
		$local_ip = pts_network::get_local_ip();
		if($local_ip)
		{
			$server_ip = $local_ip;
		}
	}
	// getenv('PTS_WEBSOCKET_PORT')
	return $server_ip . ':' . $_SERVER['SERVER_PORT'];
}
function phoromatic_web_socket_server_addr()
{
	// getenv('PTS_WEBSOCKET_PORT')
	return phoromatic_web_socket_server_ip() . '/' . $_SESSION['AccountID'];
}
function phoromatic_error_page($title, $description)
{
	echo phoromatic_webui_header(array(''), '');

	$box = '<h1>' . $title . '</h1>
		<h2>' . $description . '</h2>
		<p>To fix this error, try <a onclick="javascript:window.history.back();">returning to the previous page</a>. Still having problems? Consider <a href="https://github.com/phoronix-test-suite/phoronix-test-suite/issues?state=open">opening a GitHub issue report</a>; commercial support customers should contact Phoronix Media.</p><hr /><hr />';
	echo phoromatic_webui_box($box);
	echo phoromatic_webui_footer();
}
function phoromatic_systems_needing_attention()
{
	$main = null;
	$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID, State, LastIP, LocalIP, LastCommunication FROM phoromatic_systems WHERE AccountID = :account_id AND State = 0 ORDER BY LastCommunication DESC');
	$stmt->bindValue(':account_id', $_SESSION['AccountID']);
	$result = $stmt->execute();
	if($row = $result->fetchArray())
	{
		$main .= '<div class="pts_phoromatic_info_box_area"><div style="float: left; width: 100%;"><ul><li><h1>Systems Needing Attention</h1></li><li class="light" style="font-weight: normal;">The following systems have attempted to sync with this Phoromatic account but have not been validated. When clicking on them you are able to approve or disable them from your account along with editing the system information.</li>';

		do
		{
			$ip = $row['LocalIP'];
			if($row['LastIP'] != $row['LocalIP'])
			{
				$ip .= ' / ' . $row['LastIP'];
			}

			$main .= '<a href="?systems/' . $row['SystemID'] . '/edit"><li>' . $row['Title'] . '<br /><em><strong>IP:</strong> ' . $ip . ' <strong>Last Communication:</strong> ' . $row['LastCommunication'] . '</em></li></a>';
		}
		while($row = $result->fetchArray());

		$main .= '</ul></div></div>';
	}

	return $main;
}
function phoromatic_system_id_to_name($system_id, $aid = false)
{
	return phoromatic_server::system_id_to_name($system_id, $aid);
}
function phoromatic_oldest_result_for_schedule($schedule_id)
{
	static $old_time;

	if(!isset($old_time[$schedule_id]))
	{
		$stmt = phoromatic_server::$db->prepare('SELECT UploadTime FROM phoromatic_results WHERE AccountID = :account_id AND ScheduleID = :schedule_id ORDER BY UploadTime ASC LIMIT 1');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$stmt->bindValue(':schedule_id', $schedule_id);
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$old_time[$schedule_id] = $row['UploadTime'];
	}

	return $old_time[$schedule_id];
}
function phoromatic_schedule_id_to_name($schedule_id)
{
	static $schedule_names;

	if(!isset($schedule_names[$schedule_id]))
	{
		$stmt = phoromatic_server::$db->prepare('SELECT Title FROM phoromatic_schedules WHERE AccountID = :account_id AND ScheduleID = :schedule_id');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$stmt->bindValue(':schedule_id', $schedule_id);
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$schedule_names[$schedule_id] = $row['Title'];
	}

	return $schedule_names[$schedule_id];
}

?>
