<?php
/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2014 - 2015, Michael Larabel
	Copyright (C) 2014 - 2015, Phoronix Media

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

class pushover_net extends pts_module_interface
{
	const module_name = 'Pushover.net';
	const module_version = '1.0.0';
	const module_description = 'Submit notifications to your iOS/Android mobile devices of test results in real-time as push notifications, etc. Using the Pushover.net API.';
	const module_author = 'Michael Larabel';

	private static $pushover_net_user_key = null;
	private static $result_identifier = null;

	public static function module_environment_variables()
	{
		return array('PUSHOVER_NET_USER');
	}
	public static function __startup()
	{
		$user_key = pts_env::read('PUSHOVER_NET_USER');

		if($user_key == null)
		{
			echo PHP_EOL . 'Your Pushover.net user key must be passed via the PUSHOVER_NET_USER environment variable.' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}
		if(function_exists('curl_setopt_array') == false || function_exists('curl_init') == false)
		{
			echo PHP_EOL . 'PHP5 CURL support must be installed to use this module.' . PHP_EOL;
			return pts_module::MODULE_UNLOAD;
		}

		self::$pushover_net_user_key = $user_key;
		return true;
	}
	public static function __pre_run_process(&$test_run_manager)
	{
		if($test_run_manager->get_file_name() == null)
		{
			return false;
		}

		self::$result_identifier = $test_run_manager->get_results_identifier();
		self::post_to_pushover('Now running ' . self::$result_identifier . ' in ' . $test_run_manager->get_title() . '. Estimated time to completion: ' . pts_strings::format_time($test_run_manager->get_estimated_run_time(), 'SECONDS', true, 60) . '.');
	}
	public static function __post_test_run_success(&$test_run_request)
	{
		if(self::$result_identifier == null)
		{
			return false;
		}

		self::post_to_pushover(self::$result_identifier . ' finished ' . $test_run_request->test_profile->get_title() . ' [' . $test_run_request->get_arguments_description() . '] with a result of ' . $test_run_request->active->get_result() . ' ' . $test_run_request->test_profile->get_result_scale_formatted());
	}
	public static function __post_run_process(&$test_run_manager)
	{
		if(self::$result_identifier == null)
		{
			return false;
		}

		self::post_to_pushover(self::$result_identifier . ' testing in ' . $test_run_manager->get_title() . ' finished.');
	}
	private static function post_to_pushover($message)
	{
		curl_setopt_array($ch = curl_init(), array(
			CURLOPT_URL => 'https://api.pushover.net/1/messages.json',
			CURLOPT_POSTFIELDS => array(
			'token' => 'apxocSNmXW1Ycuwd35nH9s9vPL7k7S',
			'user' => self::$pushover_net_user_key,
			'message' => $message,
			)));
		curl_exec($ch);
		curl_close($ch);
	}
}

?>
