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

function phoromatic_webui_header($left_items, $right)
{
	$ret = '<div id="pts_phoromatic_top_header">
	<div id="pts_phoromatic_logo"><a href="?"><img src="images/phoromatic_logo.png" /></a></div><ul>';

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
<div style="float: right; padding: 2px 10px; overflow: hidden;"><a href="http://openbenchmarking.org/" style="margin-right: 20px;"><img src="images/ob-white-logo.png" /></a> <a href="http://www.phoronix-test-suite.com/"><img src="images/pts-white-logo.png" /></a></div>
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

	return phoromatic_webui_header($html_links, '<form action="#" id="search"><input type="search" name="q" size="14" /><input type="submit" name="sa" value="Search" /></form>');
}
function phoromatic_web_socket_server_addr()
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

	return $server_ip . ':' . getenv('PTS_WEBSOCKET_PORT') . '/' . $_SESSION['AccountID'];
}

?>
