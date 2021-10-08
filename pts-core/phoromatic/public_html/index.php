<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2013 - 2018, Phoronix Media
	Copyright (C) 2013 - 2018, Michael Larabel

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

define('CSS_RESULT_VIEWER_PATH', '/phoromatic.css?' . date('Ymd'));

include('../phoromatic_functions.php');
phoromatic_init_web_page_setup();
pts_network::client_startup();

interface pts_webui_interface
{
	public static function preload($PATH);
	public static function page_title();
	public static function page_header();
	public static function render_page_process($PATH);
}

// Workaround for some web servers like Mongoose with currently broken REQUEST_URI for our purposes, https://code.google.com/p/phpdesktop/issues/detail?id=137
if(strpos($_SERVER['REQUEST_URI'], '?') === false && isset($_SERVER['QUERY_STRING']))
{
	$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
}

$URI = substr($_SERVER['REQUEST_URI'], strpos($_SERVER['REQUEST_URI'], '?') + 1);
$PATH = explode('/', $URI);
$PAGE_REQUEST = str_replace('.', '', array_shift($PATH));

if($PAGE_REQUEST == 'logout' || (isset($_SESSION['AccountID']) && $_SESSION['CoreVersionOnSignOn'] != PTS_CORE_VERSION))
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
	if($_SESSION['AdminLevel'] == -40 && (stripos($PAGE_REQUEST, 'admin') === false && stripos($PAGE_REQUEST, 'result') === false) && $PAGE_REQUEST != 'logout')
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

if(substr($PAGE_REQUEST, 0, 2) == 'r_' || isset($_GET['download']) || isset($_GET['export']))
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
<!DOCTYPE html>
<html>
<head>
<script src="/phoromatic.js?<?php echo date('Ymd') . PTS_CORE_VERSION; ?>" type="text/javascript"></script>
<title>Phoronix Test Suite <?php echo PTS_VERSION; ?> - Phoromatic - <?php echo $page_class::page_title(); ?></title>
<link href="<?php echo CSS_RESULT_VIEWER_PATH; ?>" rel="stylesheet" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="keywords" content="Phoronix Test Suite, open-source benchmarking, Linux benchmarking, automated testing" />
<meta name="Description" content="Phoronix Test Suite local control server." />
<link rel="shortcut icon" href="favicon.ico" />
<?php

if(isset($_SESSION['UserID']))
{
	echo '<link rel="alternate" type="application/rss+xml" title="RSS - Test Results" href="/rss.php?user=' . $_SESSION['UserID'] . '&amp;v=' . sha1($_SESSION['CreatedOn']) . '" />';
}
?>
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
	phoromatic_server::close_database();
}
?></body>
</html>
