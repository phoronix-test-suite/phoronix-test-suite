<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013, Phoronix Media
	Copyright (C) 2013, Michael Larabel

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

$DEBUG_START_TIME = microtime(true);

define('PTS_MODE', 'WEB_CLIENT');
define('PTS_AUTO_LOAD_OBJECTS', true);

include('../pts-core.php');
pts_client::init();

function pts_webui_load_interface($interface, $PATH)
{
	if(!class_exists($interface) && is_file('web-interfaces/' . $interface . '.php'))
	{
		require('web-interfaces/' . $interface . '.php');
		$response = $interface::preload($PATH);

		if($response === true)
		{
			return $interface;
		}
		else if($response === false)
		{
			return false;
		}
		else
		{
			return pts_webui_load_interface($response, $PATH);
		}
	}

	return false;
}
function pts_webui_2d_array_to_table(&$r2d)
{
	echo '<table width="100%;">';
	foreach($r2d as $tr)
	{
		echo '<tr>';
		foreach($tr as $col_i => $col)
		{
			$type = $col_i == 0 ? 'th' : 'td';
			echo '<' . $type . '>' . $col . '</' . $type . '>';
		}
		echo '</tr>';
	}
	echo '</table>';
}
function pts_webui_1d_array_to_table(&$r1d)
{
	echo '<table width="100%;">';
	foreach($r1d as $i => $td)
	{
		echo '<tr>';
		$type = $i == 0 ? 'th' : 'td';
		echo '<' . $type . ' style="text-align: center;">' . $td . '</' . $type . '>';
		echo '</tr>';
	}
	echo '</table>';
}

interface pts_webui_interface
{
	public static function preload($PATH);
	public static function page_title();
	public static function page_header();
	public static function render_page_process();
}

$PATH = explode('/', substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?') + 1));
$PAGE_REQUEST = str_replace('.', null, array_shift($PATH));

if(is_file('web-interfaces/pts_webui_' . $PAGE_REQUEST . '.php'))
{
	$webui_class = 'pts_webui_' . $PAGE_REQUEST;
}
else
{
	// or pts_webui_intro on invalidated classes
	$webui_class = 'pts_webui_home';
}

$webui_class = pts_webui_load_interface($webui_class, $PATH);

if($webui_class === false)
{
	$webui_class = pts_webui_load_interface('pts_webui_home', $PATH);
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html style="height: 100%;">
<head>
<link href="/assets/pts-web-interface.css" rel="stylesheet" type="text/css" />
<script src="/assets/pts-web-interface.js" type="text/javascript"></script>
<title><?php $page_title = $webui_class::page_title(); echo $page_title != null ? $page_title : pts_title(true); ?></title>
</head>
<body>
<div id="pts_web_container">
<div id="pts_web_container_inside">
<table id="notification_area"></table>
	<div id="pts_header">
		<div id="pts_header_left"><?php $page_header = $webui_class::page_header(); echo $page_header != null ? $page_header : '<a href="?tests">Tests</a> <a href="?results">Results</a> <a href="?system">System</a>'; ?></div>
		<div id="pts_logo_right"><a href="http://www.phoronix-test-suite.com/" target="_blank"><img src="/assets/pts-web-logo.png" /></a></div>
	</div>
	<div id="pts_main_region">
<?php $webui_class::render_page_process(); ?>
	</div>
	<div id="pts_copyright"><?php $DEBUG_END_TIME = microtime(true); $DEBUG_TIME = $DEBUG_END_TIME - $DEBUG_START_TIME; echo '<strong>Page Rendering Took: ' . $DEBUG_TIME . ' secs.</strong> '; ?>Copyright &#xA9; 2008 - <?php echo date('Y'); ?> by Phoronix Media. All trademarks used are properties of their respective owners. All rights reserved.</div>
</div>
</div>
</body>
</html>
