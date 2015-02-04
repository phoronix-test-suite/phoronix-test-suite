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

include('../phoromatic_functions.php');
phoromatic_init_web_page_setup();

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

if($PAGE_REQUEST == 'logout')
{
	unset($_SESSION['UserName']);
	unset($_SESSION['AccountID']);
	session_destroy();
}

if(!isset($_SESSION['UserName']) || !isset($_SESSION['AccountID']) || trim($_SESSION['UserName']) == null || trim($_SESSION['AccountID']) == null)
{
	// NOT LOGGED IN
	$PAGE_REQUEST = 'welcome';
}
else if(is_file('../pages/phoromatic_' . $PAGE_REQUEST . '.php'))
{
	$PAGE_REQUEST = $PAGE_REQUEST;
}
else
{
	$PAGE_REQUEST = 'main';
}

if(isset($_SESSION['AdminLevel']))
{
	if($_SESSION['AdminLevel'] == -40 && stripos($PAGE_REQUEST, 'admin') === false && $PAGE_REQUEST != 'logout')
	{
		$PAGE_REQUEST = 'admin';
	}
	else if($_SESSION['AdminLevel'] > 0 && stripos($PAGE_REQUEST, 'admin') !== false)
	{
		$PAGE_REQUEST = 'main';
	}
}

define('PAGE_REQUEST', $PAGE_REQUEST);
$page_class = 'phoromatic_' . PAGE_REQUEST;

pts_webui::websocket_setup_defines();
$page_class = pts_webui::load_web_interface($page_class, $PATH, '../pages/');

if(substr($PAGE_REQUEST, 0, 2) == 'r_' || isset($_GET['download']))
{
	// RESOURCE
	phoromatic_server::prepare_database();
	$page_class::render_page_process($PATH);

	if(phoromatic_server::$db != null)
	{
		phoromatic_server::$db->close();
	}
	return;
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<script src="/phoromatic.js?<?php echo date('Ymd') . PTS_CORE_VERSION; ?>" type="text/javascript"></script>
<title>Phoronix Test Suite - Phoromatic - <?php echo $page_class::page_title(); ?></title>
<link href="/phoromatic.css?<?php echo date('Ymd') . PTS_CORE_VERSION; ?>" rel="stylesheet" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="keywords" content="Phoronix Test Suite, open-source benchmarking, Linux benchmarking, automated testing" />
<meta name="Description" content="Phoronix Test Suite local control server." />
<link rel="shortcut icon" href="favicon.ico" />
</head>
<body>
<?php

if(!extension_loaded('sqlite3'))
{
	echo '<p><strong>PHP SQLite3 support must first be enabled before accessing the Phoromatic server.</strong></p>';
}
else
{
	phoromatic_server::prepare_database();
	$page_class::render_page_process($PATH);

	if(phoromatic_server::$db != null)
	{
		phoromatic_server::$db->close();
	}
}
?></body>
</html>
