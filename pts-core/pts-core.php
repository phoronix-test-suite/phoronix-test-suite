<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2024, Phoronix Media
	Copyright (C) 2008 - 2024, Michael Larabel
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

class pts_core
{
	public static function init()
	{
		//set_time_limit(0);
		pts_define_directories(); // Define directories

		pts_define('PTS_INIT_TIME', time());

		if(!defined('PHP_VERSION_ID'))
		{
			$php_version = explode('.', PHP_VERSION);
			pts_define('PHP_VERSION_ID', ($php_version[0] * 10000 + $php_version[1] * 100 + $php_version[2]));
		}
	}
	public static function user_home_directory()
	{
		// Gets the system user's home directory
		static $userhome = null;

		if($userhome == null)
		{
			if(function_exists('posix_getpwuid') && function_exists('posix_getuid'))
			{
				$userinfo = posix_getpwuid(posix_getuid());
				$userhome = $userinfo['dir'];
			}
			else if(($home = getenv('HOME')))
			{
				$userhome = $home;
			}
			else if(($home = getenv('HOMEPATH')))
			{
				$userhome = getenv('HOMEDRIVE') . $home;
			}
			else if(PTS_IS_DAEMONIZED_SERVER_PROCESS)
			{
				$userhome = PTS_USER_PATH;
			}
			else if(substr(__FILE__, 0, 6) == '/home/')
			{
				$home_dir = substr(__FILE__, 0, strpos(__FILE__, '/', 7)) . '/';
				if(is_dir($home_dir) && is_dir($home_dir . '.phoronix-test-suite'))
				{
					$userhome = $home_dir;
				}
			}
			else
			{
				if(!is_writable('/'))
				{
					echo PHP_EOL . 'ERROR: Cannot find home directory.' . PHP_EOL;
				}
				$userhome = null;
			}

			$userhome = pts_strings::add_trailing_slash($userhome);
		}

		return $userhome;
	}
	public static function program_title()
	{
		// First argument was originally $show_codename, but no longer used/honored
		return 'Phoronix Test Suite v' . PTS_VERSION;
	}
}
function pts_define($name, $value = null)
{
	static $defines;

	if($name === -1)
	{
		return $defines;
	}
	else if(isset($defines[$name]))
	{
		return false;
	}

	$defines[$name] = $value;
	define($name, $value);
}
function pts_define_directories()
{
	// User's home directory for storing results, module files, test installations, etc.
	pts_define('PTS_CORE_PATH', PTS_PATH . 'pts-core/');

	if(is_dir(PTS_PATH . 'ob-cache/'))
	{
		pts_define('PTS_INTERNAL_OB_CACHE', PTS_PATH . 'ob-cache/');
	}
	else
	{
		pts_define('PTS_INTERNAL_OB_CACHE', false);
	}

	pts_define('PTS_IS_DAEMONIZED_SERVER_PROCESS', PTS_IS_CLIENT && is_writable('/var/lib/') && is_writable('/etc') ? true : false);

	if(($user_path_override = getenv('PTS_USER_PATH_OVERRIDE')) != false && is_dir($user_path_override))
	{
		pts_define('PTS_USER_PATH', $user_path_override);
	}

	if(PTS_IS_DAEMONIZED_SERVER_PROCESS)
	{
		if(!is_dir('/var/cache/phoronix-test-suite/'))
		{
			mkdir('/var/cache/phoronix-test-suite/');
		}

		pts_define('PTS_USER_PATH', '/var/lib/phoronix-test-suite/');
		pts_define('PTS_CORE_STORAGE', PTS_USER_PATH . 'core.pt2so');
		pts_define('PTS_DOWNLOAD_CACHE_PATH', '/var/cache/phoronix-test-suite/download-cache/');
		pts_define('PTS_OPENBENCHMARKING_SCRATCH_PATH', '/var/cache/phoronix-test-suite/openbenchmarking.org/');
		pts_define('PTS_TEST_PROFILE_PATH', PTS_USER_PATH . 'test-profiles/');
		pts_define('PTS_TEST_SUITE_PATH', PTS_USER_PATH . 'test-suites/');
	}
	else if(PTS_IS_CLIENT)
	{
		/* if(!is_dir(pts_core::user_home_directory() . '.phoronix-test-suite') && stripos(PHP_OS, 'win') !== false && getenv('AppData'))
		{
			pts_define('PTS_USER_PATH', getenv('AppData') . DIRECTORY_SEPARATOR . 'phoronix-test-suite' . DIRECTORY_SEPARATOR);
		}
		else
		{ */
			pts_define('PTS_USER_PATH', pts_core::user_home_directory() . '.phoronix-test-suite' . DIRECTORY_SEPARATOR);
		//}
		pts_define('PTS_CORE_STORAGE', PTS_USER_PATH . 'core.pt2so');
		pts_define('PTS_DOWNLOAD_CACHE_PATH', PTS_USER_PATH . 'download-cache/');
		pts_define('PTS_OPENBENCHMARKING_SCRATCH_PATH', PTS_USER_PATH . 'openbenchmarking.org/');
		pts_define('PTS_TEST_PROFILE_PATH', PTS_USER_PATH . 'test-profiles/');
		pts_define('PTS_TEST_SUITE_PATH', PTS_USER_PATH . 'test-suites/');
	}
	else if(defined('PTS_STORAGE_PATH'))
	{
		// e.g. OpenBenchmarking.org
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
	pts_define('PTS_CORE_STATIC_PATH', PTS_CORE_PATH . 'static/');

	if(is_dir('/usr/local/share/phoronix-test-suite/'))
	{
		pts_define('PTS_SHARE_PATH', '/usr/local/share/phoronix-test-suite/');
	}
	else if(is_dir('/usr/share/'))
	{
		pts_define('PTS_SHARE_PATH', '/usr/share/phoronix-test-suite/');
		if(is_writable('/usr/share') && !is_dir(PTS_SHARE_PATH))
		{
			mkdir(PTS_SHARE_PATH);
		}
	}
	else
	{
		pts_define('PTS_SHARE_PATH', false);
	}

	// Fallbacks below for dynamic result viewer
	if(!defined('PTS_TEST_SUITE_PATH') && defined('PTS_INTERNAL_OB_CACHE') && is_dir(PTS_INTERNAL_OB_CACHE . 'test-suites/'))
	{
		pts_define('PTS_TEST_SUITE_PATH', PTS_INTERNAL_OB_CACHE . 'test-suites/');
	}

	if(!defined('PTS_TEST_PROFILE_PATH') && defined('PTS_INTERNAL_OB_CACHE') && is_dir(PTS_INTERNAL_OB_CACHE . 'test-profiles/'))
	{
		pts_define('PTS_TEST_PROFILE_PATH', PTS_INTERNAL_OB_CACHE . 'test-profiles/');
	}

	if(!defined('PTS_OPENBENCHMARKING_SCRATCH_PATH') && defined('PTS_INTERNAL_OB_CACHE') && is_dir(PTS_INTERNAL_OB_CACHE . 'openbenchmarking.org/'))
	{
		pts_define('PTS_OPENBENCHMARKING_SCRATCH_PATH', PTS_INTERNAL_OB_CACHE . 'openbenchmarking.org/');
	}
}
function pts_needed_extensions()
{
	return array(
		// Required? - The Check If In Place - Name - Description
		// Required extesnions denoted by 1 at [0]
		array(1, extension_loaded('dom'), 'DOM', 'The Document Object Model is required for XML operations.'),
		array(1, extension_loaded('zip') || extension_loaded('zlib'), 'ZIP', 'ZIP support is required for file (de)compression.'),
		array(1, function_exists('json_decode'), 'JSON', 'JSON support is required for OpenBenchmarking.org.'),
		array(1, function_exists('simplexml_load_string'), 'SimpleXML', 'SimpleXML is required for XML operations.'),
		// Optional but recommended extensions
		array(0, extension_loaded('openssl'), 'OpenSSL', 'OpenSSL support is highly recommended to support HTTPS traffic.'),
		array(0, extension_loaded('gd'), 'GD', 'The GD library is recommended for improved graph rendering.'),
		array(0, extension_loaded('zlib'), 'Zlib', 'The Zlib extension can be used for greater file compression.'),
		array(0, function_exists('bzcompress'), 'Bzip2', 'The bzcompress/bzip2 support can be used for greater file compression.'),
		array(0, extension_loaded('sqlite3'), 'SQLite3', 'SQLite3 is required when running a Phoromatic server.'),
		array(0, function_exists('pcntl_fork'), 'PCNTL', 'PCNTL is highly recommended as it is required by some tests and for threading features.'),
		array(0, function_exists('posix_getpwuid'), 'POSIX', 'POSIX support is highly recommended.'),
		array(0, function_exists('curl_init'), 'CURL', 'CURL is recommended for an enhanced download experience.'),
		array(0, function_exists('socket_create_listen'), 'Sockets', 'Sockets is needed when running the Phoromatic Server.'),
		array(0, function_exists('readline'), 'Readline', 'Readline support is useful for tab-based auto-completion support.'),
		);
}

pts_define('PTS_VERSION', '10.8.5');
pts_define('PTS_CORE_VERSION', 10850);
pts_define('PTS_RELEASE_DATE', '20240324');
pts_define('PTS_CODENAME', 'Nesseby');

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
					if(is_dir($dir . '/' . $file) && ((PTS_IS_CLIENT || defined('PTS_AUTO_LOAD_ALL_OBJECTS')) || $file != 'client'))
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
	function pts_auto_load_class($to_load)
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

		return class_exists($to_load, false);
	}
	spl_autoload_register('pts_auto_load_class');
}
if(PTS_IS_CLIENT && ini_get('date.timezone') == null)
{
	$tz = null;

	// timezone_name_from_abbr was added in PHP 5.1.3. pre-5.2 really isn't supported by PTS, but don't at least error out here but let it get to proper checks...
	if(is_executable('/bin/date') && function_exists('timezone_name_from_abbr'))
	{
		$tz = timezone_name_from_abbr(trim(shell_exec('date +%Z 2> /dev/null')));
	}
	else if(strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		$tz = trim(shell_exec('powershell -NoProfile "(Get-TimeZone).BaseUtcOffset.Hours"'));
		$tz = is_numeric($tz) ? timezone_name_from_abbr('', ($tz * 60 * 60), 0) : null;
	}

	if($tz == null || !in_array($tz, timezone_identifiers_list()))
	{
		$tz = 'UTC';
	}

	date_default_timezone_set($tz);
}

?>
