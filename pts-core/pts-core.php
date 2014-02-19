<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2014, Phoronix Media
	Copyright (C) 2008 - 2014, Michael Larabel
	pts-core.php: To boot-strap the Phoronix Test Suite start-up

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

function pts_codename($full_string = false)
{
	$codename = ucwords(strtolower(PTS_CODENAME));

	return ($full_string ? 'PhoronixTestSuite/' : null) . $codename;
}
function pts_title($show_codename = false)
{
	return 'Phoronix Test Suite v' . PTS_VERSION . ($show_codename ? ' (' . pts_codename() . ')' : null);
}
function pts_define($name, $value = null)
{
	static $defines;

	if($name === -1)
	{
		return $defines;
	}

	$defines[$name] = $value;
	define($name, $value);
}
function pts_define_directories()
{
	// User's home directory for storing results, module files, test installations, etc.
	pts_define('PTS_CORE_PATH', PTS_PATH . 'pts-core/');

	if(PTS_IS_CLIENT)
	{
		pts_define('PTS_USER_PATH', pts_client::user_home_directory() . '.phoronix-test-suite/');
		pts_define('PTS_CORE_STORAGE', PTS_USER_PATH . 'core.pt2so');
		pts_define('PTS_TEMP_STORAGE', PTS_USER_PATH . 'temp.pt2so');
		pts_define('PTS_MODULE_LOCAL_PATH', PTS_USER_PATH . 'modules/');
		pts_define('PTS_MODULE_DATA_PATH', PTS_USER_PATH . 'modules-data/');
		pts_define('PTS_DOWNLOAD_CACHE_PATH', PTS_USER_PATH . 'download-cache/');
		pts_define('PTS_OPENBENCHMARKING_SCRATCH_PATH', PTS_USER_PATH . 'openbenchmarking.org/');
		pts_define('PTS_TEST_PROFILE_PATH', PTS_USER_PATH . 'test-profiles/');
		pts_define('PTS_TEST_SUITE_PATH', PTS_USER_PATH . 'test-suites/');
		pts_define('PTS_RESULTS_VIEWER_PATH', PTS_CORE_PATH . 'results-viewer/');
	}
	else if(defined('PTS_STORAGE_PATH'))
	{
		pts_define('PTS_OPENBENCHMARKING_SCRATCH_PATH', PTS_STORAGE_PATH . 'openbenchmarking.org/');
		pts_define('PTS_TEST_PROFILE_PATH', PTS_STORAGE_PATH . 'test-profiles/');
		pts_define('PTS_TEST_SUITE_PATH', PTS_STORAGE_PATH . 'test-suites/');
	}
	else if(defined('PATH_TO_PHOROMATIC_STORAGE'))
	{
		pts_define('PTS_OPENBENCHMARKING_SCRATCH_PATH', PATH_TO_PHOROMATIC_STORAGE . 'openbenchmarking.org/');
		pts_define('PTS_TEST_PROFILE_PATH', PATH_TO_PHOROMATIC_STORAGE . 'test-profiles/');
		pts_define('PTS_TEST_SUITE_PATH', PATH_TO_PHOROMATIC_STORAGE . 'test-suites/');
	}

	// Misc Locations
	pts_define('PTS_MODULE_PATH', PTS_CORE_PATH . 'modules/');
	pts_define('PTS_CORE_STATIC_PATH', PTS_CORE_PATH . 'static/');
	pts_define('PTS_COMMAND_PATH', PTS_CORE_PATH . 'commands/');
	pts_define('PTS_EXDEP_PATH', PTS_CORE_PATH . 'external-test-dependencies/');
	pts_define('PTS_OPENBENCHMARKING_PATH', PTS_CORE_PATH . 'openbenchmarking.org/');
}
function pts_needed_extensions()
{
	return array(
		// Required? - The Check If In Place - Name - Description
		// Required extesnions denoted by 1 at [0]
		array(1, extension_loaded('dom'), 'DOM', 'The PHP Document Object Model (DOM) is required for XML operations.'),
		array(1, extension_loaded('zip') || extension_loaded('zlib'), 'ZIP', 'PHP Zip support is required for file compression and decompression.'),
		array(1, function_exists('json_decode'), 'JSON', 'PHP JSON support is required for OpenBenchmarking.org communication.'),
		// Optional but recommended extensions
		array(0, extension_loaded('openssl'), 'OpenSSL', 'PHP OpenSSL support is recommended to support HTTPS traffic.'),
		array(0, extension_loaded('gd'), 'GD', 'The PHP GD library is recommended for improved graph rendering.'),
		array(0, extension_loaded('zlib'), 'Zlib', 'The PHP Zlib extension can be used for greater file compression.'),
		array(0, function_exists('pcntl_fork'), 'PCNTL', 'PHP PCNTL is highly recommended as it is required by some tests.'),
		array(0, function_exists('posix_getpwuid'), 'POSIX', 'PHP POSIX support is highly recommended.'),
		array(0, function_exists('curl_init'), 'CURL', 'PHP CURL is recommended for an enhanced download experience.'),
		array(0, is_file('/usr/share/php/fpdf/fpdf.php'), 'PHP FPDF', 'PHP FPDF is recommended if wishing to generate PDF reports.')
		);
}
function pts_version_codenames()
{
	return array(
		// Sør-Trøndelag - Norway
		'1.0' => 'Trondheim',
		'1.2' => 'Malvik',
		'1.4' => 'Orkdal',
		'1.6' => 'Tydal',
		'1.8' => 'Selbu',
		// Troms - Norway
		'2.0' => 'Sandtorg',
		'2.2' => 'Bardu',
		'2.4' => 'Lenvik',
		'2.6' => 'Lyngen',
		'2.8' => 'Torsken',
		// Aust-Agder - Norway
		'2.9' => 'Iveland', // early PTS3 development work
		'3.0' => 'Iveland',
		'3.2' => 'Grimstad',
		'3.4' => 'Lillesand',
		'3.6' => 'Arendal',
		'3.8' => 'Bygland',
		// Rogaland - Norway
		'4.0' => 'Suldal',
		'4.2' => 'Randaberg',
		'4.4' => 'Forsand',
		'4.6' => 'Utsira',
		'4.8' => 'Sokndal',
		// Tulskaya oblast / Tula Oblast region - Russia
		'5.0' => 'Plavsk',
		'5.2' => 'Khanino',
		'5.4' => 'Lipki',
		'5.6' => 'Dedilovo',
		'5.8' => 'Belev',
		);
}

pts_define('PTS_VERSION', '5.0.0m2');
pts_define('PTS_CORE_VERSION', 4920);
pts_define('PTS_CODENAME', 'PLAVSK');
pts_define('PTS_IS_CLIENT', (defined('PTS_MODE') && strstr(PTS_MODE, 'CLIENT') !== false));
pts_define('PTS_IS_WEB_CLIENT', (defined('PTS_MODE') && PTS_MODE == 'WEB_CLIENT'));
pts_define('PTS_IS_DEV_BUILD', (substr(PTS_VERSION, -2, 1) == 'm'));

if(!defined('PTS_PATH'))
{
	pts_define('PTS_PATH', dirname(dirname(__FILE__)) . '/');
}

pts_define('PTS_PHP_VERSION', phpversion());

if(PTS_IS_CLIENT || defined('PTS_AUTO_LOAD_OBJECTS'))
{
	function pts_build_dir_php_list($dir, &$files)
	{
		if($dh = opendir($dir))
		{
			while(($file = readdir($dh)) !== false)
			{
				if($file != '.' && $file != '..')
				{
					if(is_dir($dir . '/' . $file) && (PTS_IS_CLIENT || $file != 'client'))
					{
						// The client folder should contain classes exclusively used by the client
						pts_build_dir_php_list($dir . '/' . $file, $files);
					}
					else if(substr($file, -4) == '.php')
					{
						$files[substr($file, 0, -4)] = $dir . '/' . $file;
					}
				}
			}
		}
		closedir($dh);
	}
	function __autoload($to_load)
	{
		static $obj_files = null;

		if($obj_files == null)
		{
			pts_build_dir_php_list(PTS_PATH . 'pts-core/objects', $obj_files);
		}

		if(isset($obj_files[$to_load]))
		{
			include($obj_files[$to_load]);
			unset($obj_files[$to_load]);
		}
	}
}
if(PTS_IS_CLIENT && ini_get('date.timezone') == null)
{
	$tz = null;

	// timezone_name_from_abbr was added in PHP 5.1.3. pre-5.2 really isn't supported by PTS, but don't at least error out here but let it get to proper checks...
	if(is_executable('/bin/date') && function_exists('timezone_name_from_abbr'))
	{
		$tz = timezone_name_from_abbr(trim(shell_exec('date +%Z 2> /dev/null')));
	}

	if($tz == null || !in_array($tz, timezone_identifiers_list()))
	{
		$tz = 'UTC';
	}

	date_default_timezone_set($tz);
}

?>
