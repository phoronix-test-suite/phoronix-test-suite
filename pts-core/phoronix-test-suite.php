<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	phoronix-test-suite.php: The main code for initalizing the Phoronix Test Suite

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

setlocale(LC_NUMERIC, 'C');
define('PTS_PATH', dirname(dirname(__FILE__)) . '/');

// PTS_MODE types
// CLIENT = Standard Phoronix Test Suite Client
// LIB = Only load select PTS files
// SILENT = Load all normal pts-core files, but don't run client code
define('PTS_MODE', in_array(($m = getenv('PTS_MODE')), array('CLIENT', 'LIB', 'SILENT')) ? $m : 'CLIENT');

// Any PHP default memory limit should be fine for PTS, until you run image quality comparison tests that begins to consume memory
ini_set('memory_limit', '256M');

if(PTS_MODE == 'CLIENT' && ($open_basedir = ini_get('open_basedir')))
{
	$is_in_allowed_dir = false;

	foreach(explode(':', $open_basedir) as $allowed_dir)
	{
		if(strpos(PTS_PATH, $allowed_dir) === 0)
		{
			$is_in_allowed_dir = true;
			break;
		}
	}

	if($is_in_allowed_dir == false)
	{
		echo PHP_EOL . 'ERROR: Your php.ini configuration open_basedir setting is preventing ' . PTS_PATH . ' from loading.' . PHP_EOL;
		return false;
	}
}

require(PTS_PATH . 'pts-core/pts-core.php');

if(PTS_MODE != 'CLIENT')
{
	// pts-core is acting as a library, return now since no need to run client code
	return;
}

if(ini_get('date.timezone') == null)
{
	date_default_timezone_set('UTC');
}

$sent_command = strtolower(str_replace('-', '_', (isset($argv[1]) ? $argv[1] : null)));
$quick_start_options = array('dump_possible_options');
define('QUICK_START', in_array($sent_command, $quick_start_options));

pts_client::init(); // Initalize the Phoronix Test Suite (pts-core) client
$pass_args = array();
//stream_wrapper_register('phoronix', 'pts_phoronix_stream') or die('Failed To Initialize The Phoronix Stream');

if(is_file(PTS_PATH . 'pts-core/commands/' . $sent_command . '.php') == false)
{
	$replaced = false;

	if(pts_module::valid_run_command($sent_command))
	{
		$replaced = true;
	}
	else if(strpos($argv[1], '.openbenchmarking') !== false && is_readable($argv[1]))
	{
		// OpenBenchmarking.org launcher
		$dom = new DOMDocument();
		$dom->loadHTMLFile($argv[1]);
		$openbenchmarking_id = $dom->getElementsByTagName('html')->item(0)->getElementsByTagName('body')->item(0)->getElementsByTagName('h1')->item(0)->nodeValue;
		$sent_command = 'benchmark';
		array_push($pass_args, $openbenchmarking_id);
		$replaced = true;
	}
	else
	{
		$alias_file = pts_file_io::file_get_contents(PTS_CORE_STATIC_PATH . 'lists/option-command-aliases.list');

		foreach(pts_strings::trim_explode("\n", $alias_file) as $alias_line)
		{
			list($link_cmd, $real_cmd) = pts_strings::trim_explode('=', $alias_line);

			if($link_cmd == $sent_command)
			{
				$sent_command = $real_cmd;
				$replaced = true;
				break;
			}
		}
	}

	if($replaced == false)
	{
		// Show help command, since there are no valid commands
		$sent_command = 'help';
	}
}

define('PTS_USER_LOCK', PTS_USER_PATH . 'run_lock');

if(!QUICK_START)
{
	if(pts_client::create_lock(PTS_USER_LOCK) == false)
	{
		pts_client::$display->generic_warning('It appears that the Phoronix Test Suite is already running.\nFor proper results, only run one instance at a time.');
	}

	register_shutdown_function(array('pts_client', 'process_shutdown_tasks'));

	if(($proxy_address = pts_config::read_user_config(P_OPTION_NET_PROXY_ADDRESS, false)) && ($proxy_port = pts_config::read_user_config(P_OPTION_NET_PROXY_PORT, false)))
	{
		define('NETWORK_PROXY', $proxy_address . ':' . $proxy_port);
		define('NETWORK_PROXY_ADDRESS', $proxy_address);
		define('NETWORK_PROXY_PORT', $proxy_port);
	}
	else if(($env_proxy = getenv('http_proxy')) != false && count($env_proxy = pts_strings::colon_explode($env_proxy)) == 2)
	{
		define('NETWORK_PROXY', $env_proxy[0] . ':' . $env_proxy[1]);
		define('NETWORK_PROXY_ADDRESS', $env_proxy[0]);
		define('NETWORK_PROXY_PORT', $env_proxy[1]);
	}

	define('NETWORK_TIMEOUT', pts_config::read_user_config(P_OPTION_NET_TIMEOUT, 20));

	if(ini_get('allow_url_fopen') == 'Off')
	{
		echo PHP_EOL . 'The allow_url_fopen option in your PHP configuration must be enabled for network support.' . PHP_EOL . PHP_EOL;
		define('NO_NETWORK_COMMUNICATION', true);
	}
	else if(pts_config::read_bool_config(P_OPTION_NET_NO_NETWORK, 'FALSE'))
	{
		define('NO_NETWORK_COMMUNICATION', true);
		echo PHP_EOL . 'Network Communication Is Disabled For Your User Configuration.' . PHP_EOL . PHP_EOL;
	}
	/* else
	{
		$server_response = pts_network::http_get_contents('http://www.phoronix-test-suite.com/PTS', false, false);

		if($server_response != 'PTS')
		{
			define('NO_NETWORK_COMMUNICATION', true);
		}
	}*/

	if(!defined('NO_NETWORK_COMMUNICATION') && ini_get('file_uploads') == 'Off')
	{
		echo PHP_EOL . 'The file_uploads option in your PHP configuration must be enabled for network support.' . PHP_EOL . PHP_EOL;
	}

	if(pts_client::read_env('PTS_IGNORE_MODULES') == false)
	{
		pts_client::module_framework_init(); // Initialize the PTS module system
	}
}

// Read passed arguments
for($i = 2; $i < $argc && isset($argv[$i]); $i++)
{
	array_push($pass_args, $argv[$i]);
}

if(!QUICK_START)
{
	pts_client::user_agreement_check($sent_command);
	pts_client::user_hardware_software_reporting();
	pts_client::program_requirement_checks(true);

	// OpenBenchmarking.org
	pts_openbenchmarking::refresh_repository_lists();
}

pts_client::execute_command($sent_command, $pass_args); // Run command

if(!QUICK_START)
{
	pts_client::release_lock(PTS_USER_LOCK);
}

?>
