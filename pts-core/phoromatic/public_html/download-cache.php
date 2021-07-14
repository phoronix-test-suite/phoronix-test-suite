<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2018, Phoronix Media
	Copyright (C) 2009 - 2018, Michael Larabel

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

// INIT
define('PHOROMATIC_SERVER', true);
ini_set('memory_limit', '4G');
define('PTS_MODE', 'WEB_CLIENT');
define('PTS_AUTO_LOAD_OBJECTS', true);
//error_reporting(E_ALL);

include('../../pts-core.php');
pts_core::init();

if(isset($_GET['repo']))
{
	readfile(phoromatic_server::find_download_cache());
	pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' requested a copy of the download cache JSON');
	exit;
}
else if(isset($_GET['download']))
{
	$requested_file = str_replace(array('..', '/'), '', $_GET['download']);

	pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' is attempting to download ' . $requested_file . ' from the download cache');
	if(($dc = pts_client::download_cache_path()) && is_file($dc . $requested_file))
	{
		$file_path = $dc . $requested_file;
	}
	else if(is_file(PTS_DOWNLOAD_CACHE_PATH . $requested_file))
	{
		$file_path = PTS_DOWNLOAD_CACHE_PATH . $requested_file;
	}
	else if(is_file(PTS_SHARE_PATH . 'download-cache/' . $requested_file))
	{
		$file_path = PTS_SHARE_PATH . 'download-cache/' . $requested_file;
	}
	else if(is_file('/var/cache/phoronix-test-suite/download-cache/' . $requested_file))
	{
		$file_path = '/var/cache/phoronix-test-suite/download-cache/' . $requested_file;
	}
	else
	{
		pts_logger::add_to_log($requested_file . ' could not be found in the download cache');
		exit;
	}

	//pts_logger::add_to_log($requested_file . ' to be downloaded from ' . $file_path);
	ob_end_clean();

	if(isset($_GET['m']) && $_GET['m'])
	{
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename="'. basename($file_path). '"');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($file_path));
	}

	readfile($file_path);
	exit;
}
else
{
	echo '<h1>Phoromatic Server Download Cache</h1>';
	$possible_paths = array(pts_config::read_path_config('PhoronixTestSuite/Options/Installation/CacheDirectory', PTS_DOWNLOAD_CACHE_PATH), PTS_DOWNLOAD_CACHE_PATH, PTS_SHARE_PATH . 'download-cache/', '/var/cache/phoronix-test-suite/download-cache/');
	$files = array();
	foreach($possible_paths as $possible_path)
	{
		foreach(pts_file_io::glob($possible_path . '/*') as $file)
		{
			if(is_readable($file))
			{
				$basename = basename($file);
				if(!in_array($basename, $files))
				{
					echo '<p><a href="?m=1&download=' . $basename . '">' . $basename . ' </a></p>' . PHP_EOL;
					array_push($files, $basename);
				}
			}
		}
	}
}

?>
