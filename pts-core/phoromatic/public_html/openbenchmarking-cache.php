<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2018, Phoronix Media
	Copyright (C) 2014 - 2018, Michael Larabel

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
//ini_set('memory_limit', '64M');
define('PTS_MODE', 'WEB_CLIENT');
define('PTS_AUTO_LOAD_OBJECTS', true);
//error_reporting(E_ALL);

include('../../pts-core.php');
pts_core::init();

if(isset($_GET['index']))
{
	$requested_repo = str_replace(array('..', '/'), '', $_GET['repo']);
	$repo_index = pts_openbenchmarking::read_repository_index($requested_repo, false);
	echo $repo_index;
	pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' downloaded a copy of the ' . $requested_repo . ' OpenBenchmarking.org repository index');
}
else if(isset($_GET['repos']))
{
	$index_files = pts_file_io::glob(PTS_OPENBENCHMARKING_SCRATCH_PATH . '*.index');
	$json_repos = array();

	foreach($index_files as $index_file)
	{
		$index_data = json_decode(file_get_contents($index_file), true);
		$json_repos['repos'][basename($index_file, '.index')] = array(
			'title' => basename($index_file, '.index'),
			'generated' => $index_data['main']['generated'],
			);
	}
	echo json_encode($json_repos);
	pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' downloaded a copy of the linked repositories');
}
else if(isset($_GET['suite']))
{
	$repo = str_replace(array('..', '/'), '', $_GET['repo']);
	$test = str_replace(array('..', '/'), '', $_GET['test']);

	if(pts_openbenchmarking::is_repository($repo))
	{
		pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' requested a copy of the ' . $repo . '/' . $test . ' test suite');
		$realpath_file = realpath(PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo . '/' . $test . '.zip');

		if(is_file($realpath_file) && strpos($realpath_file, PTS_OPENBENCHMARKING_SCRATCH_PATH) === 0)
		{
			echo base64_encode(file_get_contents($realpath_file));
		}
	}
}
else if(isset($_GET['is_test']))
{
	$is_test = pts_test_profile::is_test_profile($_GET['test']);
	pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' checked to see if "' . $_GET['test'] . '" is a test: ' . ($is_test ? 'YES' : 'NO'));
	echo $is_test;
}
else if(isset($_GET['test']))
{
	$repo = str_replace(array('..', '/'), '', $_GET['repo']);
	$test = str_replace(array('..', '/'), '', $_GET['test']);

	// First see if zip is already in a cache/zip
	if(pts_openbenchmarking::is_repository($repo))
	{
		pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' requested a copy of the ' . $repo . '/' . $test . ' test profile');
		$realpath_file = realpath(PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo . '/' . $test . '.zip');

		if(is_file($realpath_file) && strpos($realpath_file, PTS_OPENBENCHMARKING_SCRATCH_PATH) === 0)
		{
			echo base64_encode(file_get_contents($realpath_file));
			exit;
		}
	}

	if(($tp_identifier = pts_test_profile::is_test_profile($repo . '/' . $test)))
	{
		// Object is a test
		$test_profile = new pts_test_profile($tp_identifier);
		if(true) // previously had pts_validation::validate_test_profile($test_profile) TODO decide whether validate_test_profile is really desired for local tests where they might be sloppy
		{
			pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' requested a fallback copy of the ' . $test_profile->get_identifier() . ' test profile');
			$zip_file = tempnam(sys_get_temp_dir(), 'phoromatic-zip') . '.zip';
			pts_file_io::unlink(substr($zip_file, 0, -4)); // clear the original tempnam without .zip
			$zip_created = pts_compression::zip_archive_create($zip_file, pts_file_io::glob($test_profile->get_resource_dir() . '*'));
			if($zip_created == false)
			{
				//echo PHP_EOL . 'Failed to create zip file.' . PHP_EOL;
				pts_file_io::unlink($zip_file);
				return false;
			}

			echo base64_encode(file_get_contents($zip_file));
			pts_file_io::unlink($zip_file);
			exit;
		}
	}
	pts_logger::add_to_log($_SERVER['REMOTE_ADDR'] . ' requested a copy of the ' . $repo . '/' . $test . ' test profile but failed to serve.');
}
?>
