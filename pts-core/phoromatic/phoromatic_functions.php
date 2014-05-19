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

function phoromatic_user_friendly_timedate($time)
{
	return date('j F H:i', strtotime($time));
}
function phoromatic_webui_header($left_items, $right)
{
	$ret = '<div id="pts_phoromatic_top_header">
	<div id="pts_phoromatic_logo"><a href="?"><img src="/images/phoromatic_logo.png" /></a></div><ul>';

	foreach($left_items as $item)
	{
		$ret .= '<li>' . $item . '</li>';
	}
	$ret .= '</ul><div style="float: right; padding: 25px 70px 0 0;">' . $right .'</div></div>';

	return $ret;
}
function phoromatic_webui_main($main, $right)
{
	return '<div id="pts_phoromatic_main"><div id="pts_phoromatic_menu_right">' . $right . '</div><div id="pts_phoromatic_main_area">' . $main . '</div><div style="clear: both;"></div></div>';
}
function phoromatic_webui_box(&$box)
{
	return '<div id="pts_phoromatic_main_box"><div id="pts_phoromatic_main_box_inside">' . $box . '</div></div>';
}
function phoromatic_webui_footer()
{
	return '<div id="pts_phoromatic_bottom_footer">
<div style="float: right; padding: 2px 10px; overflow: hidden;"><a href="http://openbenchmarking.org/" style="margin-right: 20px;"><img src="/images/ob-white-logo.png" /></a> <a href="http://www.phoronix-test-suite.com/"><img src="/images/pts-white-logo.png" /></a></div>
<p style="margin: 6px 15px;">Copyright &copy; 2008 - ' . date('Y') . ' by <a href="http://www.phoronix-media.com/">Phoronix Media</a>. All rights reserved.<br />
All trademarks used are properties of their respective owners.<br />' . pts_title(true) . ' - Core Version ' . PTS_CORE_VERSION . ' - PHP ' . PHP_VERSION . '</p></div>';
}
function phoromatic_webui_header_logged_in()
{
	$html_links = array();
	$pages = array('Main', 'Systems', 'Settings', 'Schedules', 'Results');
	foreach($pages as $page)
	{
		if(strtolower($page) == PAGE_REQUEST)
		{
			array_push($html_links, '<a href="?' . strtolower($page) . '"><u>' . $page . '</u></a>');
		}
		else
		{
			array_push($html_links, '<a href="?' . strtolower($page) . '">' . $page . '</a>');
		}
	}

	return phoromatic_webui_header($html_links, '<form action="#" id="search"><input type="search" name="q" size="14" disabled="disabled" /><input type="submit" name="sa" value="Search" disabled="disabled" /></form>');
}
function phoromatic_webui_right_panel_logged_in($add = null)
{
	$right = $add;

	if($add == null)
	{
		$right .= '<ul><li>Recently Active Systems</li>';

		$stmt = phoromatic_server::$db->prepare('SELECT Title, SystemID FROM phoromatic_systems WHERE AccountID = :account_id AND State >= 0 ORDER BY LastCommunication DESC');
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


		$right .= '<hr />
			<ul>
				<li>Active Test Events</li>';

			$stmt = phoromatic_server::$db->prepare('SELECT Title, ScheduleID FROM phoromatic_schedules WHERE AccountID = :account_id AND State >= 1 ORDER BY Title ASC');
			$stmt->bindValue(':account_id', $_SESSION['AccountID']);
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
					$right .= '<li><a href="?schedules/' . $row['ScheduleID'] . '">' . $row['Title'] . '</a></li>';
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
	$right .= '<hr /><p><strong>' . date('H:i T - j F Y') . '</strong><br />' . $system_count . ' System' . ($system_count == 1 ? '' : 's') . '<br />' . $schedule_count . ' Schedule' . ($schedule_count == 1 ? '' : 's') . '<br />' . $result_count . ' Result' . ($result_count == 1 ? '' : 's') .'<br /><a href="?logout"><strong>Log-Out</strong></a></p>';

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
function phoromatic_system_id_to_name($system_id)
{
	static $system_names;

	if(!isset($system_names[$system_id]))
	{
		$stmt = phoromatic_server::$db->prepare('SELECT Title FROM phoromatic_systems WHERE AccountID = :account_id AND SystemID = :system_id');
		$stmt->bindValue(':account_id', $_SESSION['AccountID']);
		$stmt->bindValue(':system_id', $system_id);
		$result = $stmt->execute();
		$row = $result->fetchArray();
		$system_names[$system_id] = $row['Title'];
	}

	return $system_names[$system_id];
}

?>
