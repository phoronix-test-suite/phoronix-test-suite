<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2015, Phoronix Media
	Copyright (C) 2013 - 2015, Michael Larabel

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

interface pts_webui_interface
{
	public static function preload($PATH);
	public static function page_title();
	public static function page_header();
	public static function render_page_process($PATH);
}

$URI = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?') + 1);
$PATH = explode('/', $URI);
$PAGE_REQUEST = str_replace('.', '', array_shift($PATH));

if(is_file('web-interfaces/pts_webui_' . $PAGE_REQUEST . '.php'))
{
	$webui_class = 'pts_webui_' . $PAGE_REQUEST;
}
else if(is_file('html/' . $PAGE_REQUEST . '.html'))
{
	$webui_class = $PAGE_REQUEST;
}
else
{
	// or pts_webui_intro on invalidated classes
	$webui_class = 'pts_webui_loader';
}

pts_webui::websocket_setup_defines();
$webui_class = pts_webui::load_web_interface($webui_class, $PATH, 'web-interfaces/', 'html/');

if($webui_class === false)
{
	$webui_class = pts_webui::load_web_interface('pts_webui_main', $PATH, 'web-interfaces/', 'html/');
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html style="height: 100%;">
<head>
<link href="assets/pts-web-interface.css" rel="stylesheet" type="text/css" />
<?php if(stripos($_SERVER['HTTP_USER_AGENT'], 'iPod') || stripos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || stripos($_SERVER['HTTP_USER_AGENT'], 'Android')) { ?>
<link href="assets/pts-mobile-interface.css" rel="stylesheet" type="text/css" />
<?php } ?>
<script src="js/pts-web-interface.js" type="text/javascript"></script>
<script src="js/pts-web-socket.js" type="text/javascript"></script>
<script src="js/pts-web-functions.js" type="text/javascript"></script>
<title><?php $page_title = class_exists($webui_class) ? $webui_class::page_title() : null; echo $page_title != null ? $page_title . ' - Phoronix Test Suite' : pts_core::program_title(); ?></title>
</head>
<body>
<script type="text/javascript">
	var window_size = {
		width: window.innerWidth || document.body.clientWidth,
		height: window.innerHeight || document.body.clientHeight
		};
	var pts_web_socket = new pts_web_socket();
</script>
<div id="pts_web_container">
<div id="pts_web_container_inside">
<table id="notification_area"></table>
	<?php $page_header = class_exists($webui_class) ? $webui_class::page_header() : null; if($page_header !== -1) { ?>
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
		<div id="pts_benchmark_button_area"><script type="text/javascript"> update_benchmark_button(); </script></div>
	</div>
	<?php } // $page_header !== -1 ?>
	<div id="pts_main_region">
<?php $page_ret = class_exists($webui_class) ? $webui_class::render_page_process($PATH) : -1;

if($page_ret == -1 && is_file('html/' . $webui_class . '.html'))
{
	include('html/' . $webui_class . '.html');
}

?>
	</div>
	<div id="pts_copyright"><?php //$DEBUG_END_TIME = microtime(true); $DEBUG_TIME = $DEBUG_END_TIME - $DEBUG_START_TIME; echo '<strong>Page Rendering Took: ' . $DEBUG_TIME . ' secs.</strong> '; ?>Copyright &#xA9; 2008 - <?php echo date('Y'); ?> by Phoronix Media. All trademarks used are properties of their respective owners. All rights reserved. <strong><?php echo pts_core::program_title(); ?></strong></div>
<script type="text/javascript">
	pts_web_socket.connect();
</script>
</div>
</div>
</body>
</html>
