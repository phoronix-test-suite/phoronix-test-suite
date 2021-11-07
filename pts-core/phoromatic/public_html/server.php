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

if(isset($_GET['phoromatic_info']))
{
	define('PHOROMATIC_SERVER', true);
	//ini_set('memory_limit', '64M');
	define('PTS_MODE', 'WEB_CLIENT');
	define('PTS_AUTO_LOAD_OBJECTS', true);
	error_reporting(E_ALL);
	include('../../pts-core.php');
	pts_core::init();

	$json_info = array(
		'http_server' => (isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : null),
		'pts' => pts_core::program_title(),
		'pts_core' => PTS_CORE_VERSION,
		'ws_port' => getenv('PTS_WEBSOCKET_PORT'),
		'download_cache' => '/download-cache.php',
		'openbenchmarking_cache' => '/openbenchmarking-cache.php',
		);

	echo json_encode($json_info);
	pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' requested the Phoromatic Server deployment details');
}
else
{
	include('../../pts-core.php');
	echo pts_core::program_title() . ' Phoromatic Server [' . $_SERVER['SERVER_SOFTWARE'] . ']';
}

?>
