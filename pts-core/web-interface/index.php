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
		if(count($tr) == 1)
		{
			echo '<th colspan="2" style="text-align: center;">' . $tr[0] . '</th>';
		}
		else
		{
			foreach($tr as $col_i => $col)
			{
				$type = $col_i == 0 ? 'th' : 'td';
				echo '<' . $type . '>' . $col . '</' . $type . '>';
			}
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
	public static function render_page_process($PATH);
}

$URI = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?') + 1);
$PATH = explode('/', $URI);
$PAGE_REQUEST = str_replace('.', null, array_shift($PATH));

if(is_file('web-interfaces/pts_webui_' . $PAGE_REQUEST . '.php'))
{
	$webui_class = 'pts_webui_' . $PAGE_REQUEST;
}
else
{
	// or pts_webui_intro on invalidated classes
	$webui_class = 'pts_webui_loader';
}

$webui_class = pts_webui_load_interface($webui_class, $PATH);

if($webui_class === false)
{
	$webui_class = pts_webui_load_interface('pts_webui_main', $PATH);
}
define('PTS_WEBSOCKET_SERVER', 'ws://' . $_SERVER['REMOTE_ADDR'] . ':' . getenv('PTS_WEBSOCKET_PORT') . '/');
setcookie('pts_websocket_server', PTS_WEBSOCKET_SERVER, (time() + 60 * 60 * 24), '/');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html style="height: 100%;">
<head>
<link href="assets/pts-web-interface.css" rel="stylesheet" type="text/css" />
<script src="js/pts-web-interface.js" type="text/javascript"></script>
<script src="js/pts-web-socket.js" type="text/javascript"></script>
<script src="js/pts-web-functions.js" type="text/javascript"></script>
<title><?php $page_title = $webui_class::page_title(); echo $page_title != null ? $page_title . ' - Phoronix Test Suite' : pts_title(true); ?></title>
</head>
<body>
<script type="text/javascript">
	var pts_web_socket = new pts_web_socket();
</script>
<div id="pts_web_container">
<div id="pts_web_container_inside">
<table id="notification_area"></table>
	<?php $page_header = $webui_class::page_header(); if($page_header !== -1) { ?>
	<div id="pts_header">
		<div id="pts_header_left"><?php
		$custom_header = true;
		if($page_header == null)
		{	$custom_header = false;
			$page_header = array('Main' => 'main', 'Tests' => 'tests', 'Results' => 'results', 'System' => 'system');
		}
		else if(is_array($page_header) && !isset($page_header['Main']))
		{
			$page_header = array('Main' => 'main') + $page_header;
		}

		if(is_array($page_header))
		{
			$new_header = null;
			foreach($page_header as $page => $url)
			{
				if($PAGE_REQUEST == $url || $URI == $url)
				{
					$new_header .= '<a href="?' . $url . '"><span class="dark_alt">' . $page . '</span></a> ';
				}
				else
				{
					if($custom_header && $page == 'Main')
					{
						$new_header .= '<a href="?' . $url . '"><span class="alt">' . $page . '</span></a> ';
					}
					else
					{
						$new_header .= '<a href="?' . $url . '">' . $page . '</a> ';
					}
				}
			}

			$page_header = rtrim($new_header);
		}

		echo $page_header; ?></div>
		<div id="pts_logo_right"><a href="http://www.phoronix-test-suite.com/" target="_blank"><img src="/assets/pts-web-logo.png" /></a></div>
		<script type="text/javascript">
			if(localStorage.test_queue)
			{
				var test_queue = JSON.parse(localStorage.test_queue);
				document.write('<a href=""><div id="pts_benchmark_button">' + test_queue.length + ' Tests Queued To Benchmark</div></a>');
			}
		</script>
	</div>
	<?php } // $page_header !== -1 ?>
	<div id="pts_main_region">
<?php $webui_class::render_page_process($PATH); ?>
	</div>
	<div id="pts_copyright"><?php //$DEBUG_END_TIME = microtime(true); $DEBUG_TIME = $DEBUG_END_TIME - $DEBUG_START_TIME; echo '<strong>Page Rendering Took: ' . $DEBUG_TIME . ' secs.</strong> '; ?>Copyright &#xA9; 2008 - <?php echo date('Y'); ?> by Phoronix Media. All trademarks used are properties of their respective owners. All rights reserved. <strong><?php echo pts_title(true); ?></strong></div>
<script type="text/javascript">
	pts_web_socket.connect();
</script>
</div>
</div>
</body>
</html>
