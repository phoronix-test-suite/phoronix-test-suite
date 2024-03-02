<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2023, Phoronix Media
	Copyright (C) 2010 - 2023, Michael Larabel

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

class pts_openbenchmarking
{
	protected static $openbenchmarking_index_refreshed = false;

	public static function stats_hardware_list()
	{
		return array(
			'cpu' => array('cpu', 'model'),
			'cpu_count' => array('cpu', 'core-count'),
			'cpu_speed' => array('cpu', 'mhz-default-frequency'),
			'chipset' => array('chipset'),
			'motherboard' => array('motherboard'),
			'gpu' => array('gpu', 'model'),
			'disk' => array('disk', 'identifier'),
			'audio' => array('audio', 'identifier'),
			'monitor' => array('monitor', 'identifier')
			);
	}
	public static function stats_software_list()
	{
		return array(
			'os' => array('system', 'operating-system'),
			'os_architecture' => array('system', 'kernel-architecture'),
			'kernel' => array('system', 'kernel'),
			'display_server' => array('system', 'display-server'),
			'display_driver' => array('system', 'display-driver-string'),
			'opengl' => array('system', 'opengl-driver'),
			'desktop' => array('system', 'desktop-environment'),
			'compiler' => array('system', 'compiler'),
			'file_system' => array('system', 'filesystem'),
			'screen_resolution' => array('gpu', 'screen-resolution-string')
			);
	}
	public static function openbenchmarking_standards_path()
	{
		return PTS_CORE_PATH . 'openbenchmarking.org/';
	}
	public static function is_valid_gsid_format($gsid)
	{
		$gsid_valid = false;

		if(strlen($gsid) == 9 && pts_strings::is_upper(substr($gsid, 0, 6)) && pts_strings::is_digit(substr($gsid, 6, 3)))
		{
			$gsid_valid = true;
		}

		return $gsid_valid;
	}
	public static function is_valid_gsid_e_format($gside)
	{
		$gside_valid = false;

		if(strlen($gside) == 12)
		{
			if(substr($gside, 0, 10) == strtoupper(substr($gside, 0, 10)) && is_numeric(substr($gside, 10, 2)))
			{
				$gside_valid = true;
			}
		}

		return $gside_valid;
	}
	public static function is_valid_gsid_p_format($gsidp)
	{
		$gsidp_valid = false;

		if(strlen($gsidp) == 10)
		{
			if(substr($gsidp, 0, 9) == strtoupper(substr($gsidp, 0, 9)) && is_numeric(substr($gsidp, 9, 1)))
			{
				$gsidp_valid = true;
			}
		}

		return $gsidp_valid;
	}
	public static function is_openbenchmarking_result_id($id)
	{
		$is_id = false;

		if(self::is_string_openbenchmarking_result_id_compliant($id) && pts_network::internet_support_available())
		{
			$json_response = pts_openbenchmarking::make_openbenchmarking_request('is_openbenchmarking_result', array('i' => $id));
			$json_response = json_decode($json_response, true);

			if(is_array($json_response) && isset($json_response['openbenchmarking']['result']['valid']) && $json_response['openbenchmarking']['result']['valid'] == 'TRUE')
			{
				$is_id = true;
			}
		}

		return $is_id;
	}
	public static function result_uploads_from_this_ip()
	{
		$results = array();

		if(pts_network::internet_support_available())
		{
			$json_response = pts_openbenchmarking::make_openbenchmarking_request('result_uploads_from_this_ip');
			$json_response = json_decode($json_response, true);

			if(is_array($json_response) && isset($json_response['uploads']['public_ids']))
			{
				$results = $json_response['uploads']['public_ids'];
			}
		}

		return $results;
	}
	public static function possible_phoromatic_servers()
	{
		$results = array();

		if(pts_network::internet_support_available())
		{
			$json_response = pts_openbenchmarking::make_openbenchmarking_request('phoromatic_server_relay_check');
			$json_response = json_decode($json_response, true);

			if(is_array($json_response) && isset($json_response['phoromatic']['possible_servers']))
			{
				$results = $json_response['phoromatic']['possible_servers'];
			}
		}

		return $results;
	}
	public static function clone_openbenchmarking_result(&$id, $return_xml = false)
	{
		if(!pts_network::internet_support_available())
		{
			return false;
		}

		$json_response = pts_openbenchmarking::make_openbenchmarking_request('clone_openbenchmarking_result', array('i' => $id));
		$json_response = json_decode($json_response, true);
		$valid = false;

		if(is_array($json_response) && isset($json_response['openbenchmarking']['result']['composite_xml']))
		{
			$composite_xml = $json_response['openbenchmarking']['result']['composite_xml'];

			$result_file = new pts_result_file($composite_xml);
			$result_file->set_reference_id($id);
			//$id = strtolower($id);
			$valid = $return_xml ? $result_file->get_xml() : pts_client::save_test_result($id . '/composite.xml', $result_file->get_xml(), true);

			if(PTS_IS_CLIENT && $json_response['openbenchmarking']['result']['system_logs_available'])
			{
				// Fetch the system logs and toss them into the results directory system-logs/
				pts_openbenchmarking::clone_openbenchmarking_result_system_logs($id, pts_client::setup_test_result_directory($id), $json_response['openbenchmarking']['result']['system_logs_available']);
			}
		}
		else if(PTS_IS_CLIENT && isset($json_response['openbenchmarking']['result']['error']))
		{
			trigger_error($json_response['openbenchmarking']['result']['error'], E_USER_ERROR);
		}

		return $valid;
	}
	public static function clone_openbenchmarking_result_system_logs(&$id, $extract_to, $sha1_compare = null)
	{
		if(!pts_network::internet_support_available())
		{
			return false;
		}

		$system_log_response = pts_openbenchmarking::make_openbenchmarking_request('clone_openbenchmarking_system_logs', array('i' => $id));
		$extracted = false;

		if($system_log_response != null)
		{
			$zip_temp = pts_client::create_temporary_file();
			file_put_contents($zip_temp, $system_log_response);

			if($sha1_compare == null || sha1_file($zip_temp) == $sha1_compare)
			{
				// hash check of file passed or was null
				$extracted = pts_compression::zip_archive_extract($zip_temp, $extract_to);
			}

			unlink($zip_temp);
		}

		return $extracted;
	}
	public static function is_string_openbenchmarking_result_id_compliant($id)
	{
		$valid = false;

		if($id != null && strlen($id) == 22)
		{
			$segments = explode('-', $id);

			if(count($segments) == 3)
			{
				if(strlen($segments[0]) == 7 && is_numeric($segments[0]))
				{
					$us = strlen($segments[1]);

					if($us > 1 && $us < 9 && pts_strings::is_alnum($segments[1]))
					{
						if(pts_strings::is_alnum($segments[2]))
						{
							$valid = true;
						}
					}
				}
			}
		}

		return $valid;
	}
	public static function is_abstract_id($id)
	{
		$valid = false;

		if(strlen($id) == 4)
		{
			if(pts_strings::is_alpha($id))
			{
				$valid = true;
			}
		}

		return $valid;
	}
	public static function openbenchmarking_host()
	{
		static $host = null;

		if($host == null)
		{
			// Use HTTPS if OpenSSL is available as a check to see if HTTPS can be handled
			// Using a proxy may have problems for HTTPS
			$host = ((extension_loaded('openssl') && getenv('NO_OPENSSL') == false && (!PTS_IS_CLIENT || !pts_network::is_proxy_setup())) ? 'https://' : 'http://') . 'openbenchmarking.org/';
			//$host = 'http://openbenchmarking.org/';
		}

		return $host;
	}
	public static function report_repository_index_updates($repo, $old_index, $new_index)
	{
		if(isset($new_index['tests']) && isset($old_index['tests']) && ($new_index['tests'] != $old_index['tests'] || $new_index['suites'] != $old_index['suites']))
		{
			$test_versions_count = 0;
			foreach($new_index['tests'] as $t)
			{
				$test_versions_count += count($t['versions']);
			}
			echo pts_client::cli_colored_text('Updated OpenBenchmarking.org Repository Index', 'green', true) . PHP_EOL;
			echo pts_client::cli_colored_text($repo . ': ' . pts_strings::plural_handler(count($new_index['tests']), 'Distinct Test') . ', ' . pts_strings::plural_handler($test_versions_count, 'Test Version') . (count($new_index['suites']) > 0 ? ', ' . pts_strings::plural_handler(count($new_index['suites']), 'Suite') : null), 'green', true) . PHP_EOL;
			$table = array();
			foreach(array_keys($new_index['tests']) as $test)
			{
				if(!isset($old_index['tests'][$test]))
				{
					$table[] = array(pts_client::cli_just_bold('New Test: '), $repo . '/' . $test, pts_client::cli_colored_text('v' . array_shift($new_index['tests'][$test]['versions']), 'gray'), $new_index['tests'][$test]['title']);
				}
				else if($new_index['tests'][$test]['versions'] != $old_index['tests'][$test]['versions'])
				{
					$version_diff = array_diff($new_index['tests'][$test]['versions'], $old_index['tests'][$test]['versions']);
					if(!empty($version_diff) && $new_index['tests'][$test]['status'] != 'Deprecated')
					{
						$table[] = array(pts_client::cli_just_bold('Updated Test: '), $repo . '/' . $test, pts_client::cli_colored_text('v' . array_shift($version_diff), 'gray'), $new_index['tests'][$test]['title']);
					}
				}
			}


			if(isset($new_index['suites']) && isset($old_index['suites']) && $new_index['suites'] != $old_index['suites'])
			{
				foreach(array_keys($new_index['suites']) as $suite)
				{
					if(!isset($old_index['suites'][$suite]))
					{
						$table[] = array(pts_client::cli_just_bold('New Suite: '), $repo . '/' . $suite, pts_client::cli_colored_text('v' . array_shift($new_index['suites'][$suite]['versions']), 'gray'), $new_index['suites'][$suite]['title']);
					}
					else if($new_index['suites'][$suite]['versions'] != $old_index['suites'][$suite]['versions'])
					{
						$version_diff = array_diff($new_index['suites'][$suite]['versions'], $old_index['suites'][$suite]['versions']);
						if(!empty($version_diff) && $new_index['suites'][$suite]['status'] != 'Deprecated')
						{
							$table[] = array(pts_client::cli_just_bold('Updated Suite: '), $repo . '/' . $suite, pts_client::cli_colored_text('v' . array_shift($version_diff), 'gray'), $new_index['suites'][$suite]['title']);
						}
					}
				}
			}
			if(!empty($table))
			{
				echo pts_client::cli_just_italic('Available Changes From ' . date('j F' . (date('Y') != date('Y', $old_index['main']['generated']) ? ' Y' : ''), $old_index['main']['generated']) . ' To ' . date('j F', $new_index['main']['generated'])) . PHP_EOL;
				echo pts_user_io::display_text_table($table) . PHP_EOL;
			}
		}
	}
	public static function get_generated_time_from_index($index_file)
	{
		$generated_time = -1;
		if(is_file($index_file))
		{
			$repo_index = json_decode(file_get_contents($index_file), true);
			if(isset($repo_index['main']['generated']))
			{
				$generated_time = $repo_index['main']['generated'];
			}
		}
		return $generated_time;
	}
	public static function refresh_repository_lists($repos = null, $force_refresh = false)
	{
		if($repos == null)
		{
			if($force_refresh == false)
			{
				if(!defined('HAS_REFRESHED_OBO_LIST'))
				{
					pts_define('HAS_REFRESHED_OBO_LIST', true);
				}
				else
				{
					return true;
				}
			}

			$repos = self::linked_repositories();
		}

		foreach($repos as $repo_name)
		{
			pts_file_io::mkdir(PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name);

			if($repo_name == 'local')
			{
				// Local is a special case, not actually a real repository
				continue;
			}

			if(!is_dir(PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name))
			{
				mkdir(PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name, 0777, true);
			}

			$index_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . '.index';
			$server_index = null;

			if(is_file($index_file))
			{
				$repo_index = json_decode(file_get_contents($index_file), true);
				$generated_time = $repo_index['main']['generated'];

				if($force_refresh == false)
				{
					// Refreshing the indexes once every few days should be suffice
					// Refresh approximately every three days by default
					// Allow more frequent OB cache
					$index_cache_ttl = defined('OPENBENCHMARKING_BUILD') ? (1 / 24) : 1;
					if(PTS_IS_CLIENT && ($config_ttl = pts_config::read_user_config('PhoronixTestSuite/Options/OpenBenchmarking/IndexCacheTTL')))
					{
						if(is_numeric($config_ttl) && $config_ttl >= 1)
						{
							$index_cache_ttl = $config_ttl;
						}
						else
						{
							// if the value is 0 or garbage, only rely upon manual refreshes
							continue;
						}
					}

					if($generated_time > (time() - (86400 * $index_cache_ttl)) && (!defined('FIRST_RUN_ON_PTS_UPGRADE') || FIRST_RUN_ON_PTS_UPGRADE == false))
					{
						// The index is new enough
						continue;
					}
				}

				$old_index = $repo_index;

				if(pts_network::internet_support_available())
				{
					$server_index = pts_openbenchmarking::make_openbenchmarking_request('repo_index', array('repo' => $repo_name));
					self::$openbenchmarking_index_refreshed = true;
				}
				if(!$server_index && $phoromatic_cache_index = self::phoromatic_server_ob_cache_request('index', $repo_name))
				{
					// Ensure the Phoromatic cache has a newer version of the index than what's currently on the system
					$repo_index = json_decode($phoromatic_cache_index, true);
					if(isset($repo_index['main']['generated']))
					{
						$cache_generated_time = $repo_index['main']['generated'];

						if($cache_generated_time > $generated_time)
						{
							$server_index = $phoromatic_cache_index;
						}
						self::$openbenchmarking_index_refreshed = true;
					}
				}
			}
			else if(pts_network::internet_support_available())
			{
				$server_index = pts_openbenchmarking::make_openbenchmarking_request('repo_index', array('repo' => $repo_name));
				self::$openbenchmarking_index_refreshed = true;
			}

			if(!$server_index && $phoromatic_cache_index = self::phoromatic_server_ob_cache_request('index', $repo_name))
			{
				$server_index = $phoromatic_cache_index;
			}

			if($server_index != null && json_decode($server_index) != false)
			{
				/* if(PTS_IS_CLIENT)
				{
					// Reformat the JSON to pretty print
					$re_encode = json_encode(json_decode($server_index, true), JSON_PRETTY_PRINT);
					if(!empty($re_encode))
					{
						$server_index = $re_encode;
					}
				}*/

				file_put_contents($index_file, $server_index);
				if(PTS_IS_CLIENT && isset($old_index))
				{
					pts_openbenchmarking::report_repository_index_updates($repo_name, $old_index, json_decode($server_index, true));
				}
			}
			else if(PTS_IS_CLIENT && is_file('/var/cache/phoronix-test-suite/openbenchmarking.org/' . $repo_name . '.index'))
			{
				copy('/var/cache/phoronix-test-suite/openbenchmarking.org/' . $repo_name . '.index', $index_file);
			}

			if(!is_file($index_file))
			{
				static $reported_read_failure_notice;

				if(!isset($reported_read_failure_notice[$repo_name]) && PTS_IS_CLIENT)
				{
					trigger_error('Failed To Fetch OpenBenchmarking.org Repository Data: ' . $repo_name . '. ' . (!pts_network::internet_support_available() ? 'Internet connection disabled/unavailable.' : 'If this issue persists, file an issue @ https://github.com/phoronix-test-suite/phoronix-test-suite/issues'), E_USER_WARNING);
					$reported_read_failure_notice[$repo_name] = true;
				}
			}
		}
	}
	public static function openbenchmarking_has_refreshed()
	{
		return self::$openbenchmarking_index_refreshed;
	}
	public static function official_repositories()
	{
		$repos = array('local', 'pts', 'system', 'git');

		if((PTS_IS_CLIENT && phodevi::is_windows()) || defined('PHOROMATIC_SERVER_WEB_INTERFACE'))
		{
			// Various windows tests for compatibility where there isn't mainline support in the test profile otherwise
			array_unshift($repos, 'windows');
		}

		return $repos;
	}
	public static function linked_repositories()
	{
		$repos = self::official_repositories();

		if(PTS_IS_CLIENT && pts_openbenchmarking_client::user_name() != false)
		{
			$repos[] = pts_openbenchmarking_client::user_name();
		}
		$on_system_indexes = !defined('PTS_OPENBENCHMARKING_SCRATCH_PATH') ? array() : glob(PTS_OPENBENCHMARKING_SCRATCH_PATH . '*.index');
		foreach($on_system_indexes as $index)
		{
			$index = basename($index, '.index');
			pts_arrays::unique_push($repos, $index);
		}

		return $repos;
	}
	public static function make_openbenchmarking_request($request, $post = array(), $http_timeout_override = -1)
	{
		if(!pts_network::internet_support_available())
		{
			return false;
		}

		$url = pts_openbenchmarking::openbenchmarking_host() . 'f/client.php';
		$to_post = array_merge(array(
			'r' => $request,
			'client_version' => PTS_CORE_VERSION,
			'gsid' => (defined('PTS_GSID') ? PTS_GSID : null),
			'gsid_e' => (defined('PTS_GSID_E') ? PTS_GSID_E : null)
			), $post);

		if(PTS_IS_CLIENT && ($account = pts_openbenchmarking_client::get_openbenchmarking_account()) && is_array($account))
		{
			$to_post = array_merge($to_post, $account);
		}

		return pts_network::http_upload_via_post($url, $to_post, true, $http_timeout_override);
	}
	public static function is_repository($repo_name)
	{
		return is_file(PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . '.index') ? PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . '.index' : false;
	}
	public static function is_local_repo($repo_name)
	{
		return is_file(PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . '.index') ? PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . '.index' : false;
	}
	public static function ob_repo_exists($name)
	{
		$login_state = pts_openbenchmarking::make_openbenchmarking_request('repo_exists', array('s_u' => $name));
		$json = json_decode($login_state, true);

		return isset($json['openbenchmarking']['repo']['name']) ? array($json['openbenchmarking']['repo']['name'], $json['openbenchmarking']['repo']['alias']) : false;
	}
	public static function read_repository_index($repo_name, $do_decode = true)
	{
		static $caches;
		static $last_cache_times;

		if($do_decode && isset($caches[$repo_name]) && $last_cache_times[$repo_name] > (time() - 60))
		{
			return $caches[$repo_name];
		}

		$index_file = !defined('PTS_OPENBENCHMARKING_SCRATCH_PATH') ? false : PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . '.index';
		if(is_file($index_file))
		{
			$index_file = file_get_contents($index_file);

			if($do_decode)
			{
				$index_file = json_decode($index_file, true);
			}
		}
		else
		{
			$index_file = null;
		}

		if($do_decode)
		{
			$caches[$repo_name] = $index_file;
			$last_cache_times[$repo_name] = time();
		}

		return $index_file;
	}
	public static function is_test_profile_downloaded($qualified_identifier)
	{
		return is_file(PTS_TEST_PROFILE_PATH . $qualified_identifier . '/test-definition.xml');
	}
	public static function download_test_profile($qualified_identifier, $download_location = null, $cache_check = false)
	{
		if(empty($download_location))
		{
			$download_location = PTS_TEST_PROFILE_PATH;
		}
		if(is_file($download_location . $qualified_identifier . '/test-definition.xml') && !$cache_check)
		{
			return true;
		}

		$file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $qualified_identifier . '.zip';
		$json_changelog = '';
		$json_overview = '';

		if(!is_file($file))
		{
			$cache_locations = array('/var/cache/phoronix-test-suite/openbenchmarking.org/');

			foreach($cache_locations as $cache_location)
			{
				// Assuming if file is present that the SHA1 checksum is fine
				// otherwise add: && ($hash_check == null || sha1_file($cache_location . $qualified_identifier . '.zip') == $hash_check)
				if(is_file($cache_location . $qualified_identifier . '.zip'))
				{
					copy($cache_location . $qualified_identifier . '.zip', $file);
					break;
				}
			}
		}

		if(!is_file($file))
		{
			if(pts_network::internet_support_available())
			{
				$acquire_test_json = pts_openbenchmarking::make_openbenchmarking_request('acquire_test', array('i' => $qualified_identifier));
				$acquire_test_json = json_decode($acquire_test_json, true);

				if(isset($acquire_test_json['openbenchmarking']['test']['sha1_hash']) && isset($acquire_test_json['openbenchmarking']['test']['zip']))
				{
					$test_profile = base64_decode($acquire_test_json['openbenchmarking']['test']['zip']);
					$hash_check = $acquire_test_json['openbenchmarking']['test']['sha1_hash'];
					// TODO should also check for ['openbenchmarking']['test']['error'] to report any problems

					if($test_profile != null &&  $hash_check == sha1($test_profile))
					{
						// save it
						if(!is_dir(dirname($file)))
						{
							mkdir(dirname($file));
						}
						file_put_contents($file, $test_profile);

						if(isset($acquire_test_json['openbenchmarking']['test']['changes']))
						{
							$json_changelog = $acquire_test_json['openbenchmarking']['test']['changes'];
						}
						if(isset($acquire_test_json['openbenchmarking']['test']['generated']))
						{
							$json_overview = $acquire_test_json['openbenchmarking']['test']['generated'];
						}
					}
				}
			}

			if(!is_file($file) && $test_profile = self::phoromatic_server_ob_cache_request('test', substr($qualified_identifier, 0, strpos($qualified_identifier, '/')), substr($qualified_identifier, strpos($qualified_identifier, '/') + 1)))
			{
				if($b64 = base64_decode($test_profile))
				{
					$test_profile = $b64;
				}

				if(!empty($test_profile))
				{
					file_put_contents($file, $test_profile);
				}
			}

			if(PTS_IS_CLIENT && !is_file($file))
			{
				if(!defined('PHOROMATIC_SERVER'))
				{
					trigger_error('Unable to obtain ' . $qualified_identifier . ' from OpenBenchmarking.org. ' . (!pts_network::internet_support_available() ? 'Internet connection disabled/unavailable.' : 'If this issue persists, file an issue @ https://github.com/phoronix-test-suite/phoronix-test-suite/issues') . PHP_EOL, E_USER_ERROR);
				}
				return false;
			}
		}

		if(!is_file($download_location . $qualified_identifier . '/test-definition.xml') && is_file($file))
		{
			// extract it
			pts_file_io::mkdir($download_location . dirname($qualified_identifier));
			pts_file_io::mkdir($download_location . $qualified_identifier);
			pts_compression::zip_archive_extract($file, $download_location . $qualified_identifier);

			if(is_file(PTS_TEST_PROFILE_PATH . $qualified_identifier . '/test-definition.xml'))
			{
				if(!empty($json_changelog))
				{
					file_put_contents(PTS_TEST_PROFILE_PATH . $qualified_identifier . '/changelog.json', json_encode($json_changelog));
				}
				if(!empty($json_overview))
				{
					file_put_contents(PTS_TEST_PROFILE_PATH . $qualified_identifier . '/generated.json', json_encode($json_overview));
				}
				return true;
			}
			else
			{
				unlink($file);
				//trigger_error('Test definition not found for ' . $qualified_identifier . '.' . PHP_EOL, E_USER_ERROR);
				return false;
			}
		}

		return false;
	}
	public static function remote_test_profile_check($identifier)
	{
		// See if the test (e.g. a local/ test) is available for download from Phoromatic Servers that is not part of an Openbenchmarking.org repo index
		if(PTS_IS_CLIENT == false)
		{
			return false;
		}
		$is_test = self::phoromatic_server_ob_cache_request('is_test', null, $identifier);
		if(!empty($is_test) && strpos($is_test, ' ') === false && strpos($is_test, '..') === false && strpos($is_test, '/') !== false)
		{
			if($test_profile = self::phoromatic_server_ob_cache_request('test', substr($is_test, 0, strpos($is_test, '/')), substr($is_test, strpos($is_test, '/') + 1)))
			{
				$test_zip = base64_decode($test_profile);
				if(!empty($test_zip))
				{
					$zip_file = tempnam(sys_get_temp_dir(), 'phoromatic-zip') . '.zip';
					file_put_contents($zip_file, $test_zip);

					// Extract the temp zip
					$download_location = PTS_TEST_PROFILE_PATH;
					if(!is_file($download_location . $is_test . '/test-definition.xml') && is_file($zip_file))
					{
						// extract it
						pts_file_io::mkdir($download_location . dirname($is_test));
						pts_file_io::mkdir($download_location . $is_test);
						pts_compression::zip_archive_extract($zip_file, $download_location . $is_test);

						if(is_file(PTS_TEST_PROFILE_PATH . $is_test . '/test-definition.xml'))
						{
							return true;
						}
					}
					unlink($zip_file);
					unlink(substr($zip_file, 0, -4)); // clear original tempnam
				}
			}
		}
		return false;
	}
	public static function phoromatic_server_ob_cache_request($type_request, $repo = null, $test = null)
	{
		if(PTS_IS_CLIENT == false)
		{
			return null;
		}

		$archived_servers = pts_client::available_phoromatic_servers();

		foreach($archived_servers as $archived_server)
		{
			$cache = pts_network::http_get_contents($archived_server['protocol'] . '://' . $archived_server['ip'] . ':' . $archived_server['http_port'] . '/openbenchmarking-cache.php?' . $type_request . '&repo=' . $repo . '&test=' . $test);

			if(!empty($cache))
			{
				return $cache;
			}
		}

		return null;
	}
	public static function available_tests($download_tests = true, $all_versions = false, $append_versions = false, $show_deprecated_tests = false, $only_show_available_cached_tests = false)
	{
		$available_tests = array();

		foreach(self::linked_repositories() as $repo)
		{
			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			if(isset($repo_index['tests']) && is_array($repo_index['tests']))
			{
				foreach(array_keys($repo_index['tests']) as $identifier)
				{
					if(!$show_deprecated_tests && isset($repo_index['tests'][$identifier]['status']) && $repo_index['tests'][$identifier]['status'] == 'Deprecated')
					{
						continue;
					}
					if($all_versions === 2)
					{
						// When $all_versions is 2, only the latest version in each XX.YY stream is shown.
						// no reason in some cases to check each XX.YY.ZZ version but only the last
						$versions = $repo_index['tests'][$identifier]['versions'];
						$minor_series_shown = array();
						foreach($versions as $i => $v)
						{
							$version_wo_minor = substr($v, 0, strrpos($v, '.'));
							if(isset($minor_series_shown[$version_wo_minor]))
							{
								unset($versions[$i]);
							}
							$minor_series_shown[$version_wo_minor] = true;
						}
						$append_versions = true;
					}
					else if($all_versions)
					{
						$versions = $repo_index['tests'][$identifier]['versions'];
						$append_versions = true;
					}
					else
					{
						// Just get the latest version by default
						$versions = array(array_shift($repo_index['tests'][$identifier]['versions']));
					}

					foreach($versions as $version)
					{
						if($download_tests)
						{
							if(self::download_test_profile($repo . '/' . $identifier . '-' . $version) == false)
							{
								continue;
							}
						}

						if($only_show_available_cached_tests)
						{
							if(!pts_openbenchmarking::is_test_profile_downloaded($identifier . '-' . $version))
							{
								// Without Internet, won't be able to download test, so don't show it
								continue;
							}
							$test_profile = new pts_test_profile($identifier . '-' . $version);
							if(pts_test_install_request::test_files_available_via_cache($test_profile) == false)
							{
								// Without Internet, only show tests where files are local or in an available cache
								continue;
							}
						}

						$available_tests[] = $repo . '/' . $identifier . ($append_versions ? '-' . $version : null);
					}
				}
			}
		}
		$available_tests = array_unique($available_tests);

		return $available_tests;
	}
	public static function available_suites($download_suites = true, $only_show_maintained_suites = false)
	{
		$available_suites = array();

		foreach(self::linked_repositories() as $repo)
		{
			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			if(isset($repo_index['suites']) && is_array($repo_index['suites']))
			{
				foreach(array_keys($repo_index['suites']) as $identifier)
				{
					if($only_show_maintained_suites && $repo_index['suites'][$identifier]['last_updated'] < (time() - (86400 * 365 * 5)))
					{
						continue;
					}

					if($download_suites && pts_network::internet_support_available())
					{
						$version = array_shift($repo_index['suites'][$identifier]['versions']);
						if(self::download_test_suite($repo . '/' . $identifier . '-' . $version) == false)
						{
							continue;
						}
					}
					$available_suites[] = $repo . '/' . $identifier;
				}
			}
		}

		return $available_suites;
	}
	public static function download_test_suite($qualified_identifier, $download_location = null, $cache_check = false)
	{
		if(empty($download_location))
		{
			$download_location = PTS_TEST_SUITE_PATH;
		}
		if(is_file($download_location . $qualified_identifier . '/suite-definition.xml') && !$cache_check)
		{
			return true;
		}

		$file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $qualified_identifier . '.zip';

		if(!is_file($file))
		{
			if(is_file('/var/cache/phoronix-test-suite/openbenchmarking.org/' . $qualified_identifier . '.zip'))
			{
				copy('/var/cache/phoronix-test-suite/openbenchmarking.org/' . $qualified_identifier . '.zip', $file);
			}
			else if(pts_network::internet_support_available())
			{
				$acquire_suite_json = pts_openbenchmarking::make_openbenchmarking_request('acquire_suite', array('i' => $qualified_identifier));
				$acquire_suite_json = json_decode($acquire_suite_json, true);

				if(isset($acquire_suite_json['openbenchmarking']['suite']['sha1_hash']) && isset($acquire_suite_json['openbenchmarking']['suite']['zip']))
				{
					$test_suite = base64_decode($acquire_suite_json['openbenchmarking']['suite']['zip']);
					$hash_check = $acquire_suite_json['openbenchmarking']['suite']['sha1_hash'];
					// TODO should also check for ['openbenchmarking']['suite']['error'] to report any problems

					if($test_suite != null &&  $hash_check == sha1($test_suite))
					{
						// save it
						file_put_contents($file, $test_suite);
					}
				}
			}

			if(!is_file($file) && $test_suite = self::phoromatic_server_ob_cache_request('suite', substr($qualified_identifier, 0, strpos($qualified_identifier, '/')), substr($qualified_identifier, strpos($qualified_identifier, '/') + 1)))
			{
				if($b64 = base64_decode($test_suite))
				{
					$test_suite = $b64;
				}

				if(!empty($test_suite))
				{
					file_put_contents($file, $test_suite);
				}
			}

			if(PTS_IS_CLIENT && !is_file($file))
			{
				if(!defined('PHOROMATIC_SERVER'))
				{
					trigger_error('Unable to obtain ' . $qualified_identifier . ' from OpenBenchmarking.org. ' . (!pts_network::internet_support_available() ? 'Internet connection disabled/unavailable.' : 'If this issue persists, file an issue @ https://github.com/phoronix-test-suite/phoronix-test-suite/issues') . PHP_EOL, E_USER_ERROR);
				}
				return false;
			}
		}

		if(!is_file($download_location . $qualified_identifier . '/suite-definition.xml') && is_file($file))
		{
			// extract it
			pts_file_io::mkdir($download_location . dirname($qualified_identifier));
			pts_file_io::mkdir($download_location . $qualified_identifier);
			pts_compression::zip_archive_extract($file, $download_location . $qualified_identifier);

			if(is_file($download_location . $qualified_identifier . '/suite-definition.xml'))
			{
				return true;
			}
			else
			{
				unlink($file);
				return false;
			}
		}

		return false;
	}
	protected static function check_only_type_compare($check_only_type, $is_type)
	{
		return $check_only_type == false || $check_only_type === $is_type;
	}
	public static function evaluate_string_to_qualifier($supplied, $bind_version = true, $check_only_type = false)
	{
		if($supplied == null)
		{
			return false;
		}

		$qualified = false;
		$c_repo = null;
		$repos = self::linked_repositories();

		if(($c = strpos($supplied, '/')) !== false)
		{
			// A repository was explicitly defined
			$c_repo = substr($supplied, 0, $c);
			$test = substr($supplied, ($c + 1));

			// If it's in the linked repo list it should have refreshed when starting client
			if(!in_array($c_repo, $repos))
			{
				// Pull in this repository's index
				pts_openbenchmarking::refresh_repository_lists($repos);
			}

			$repos = array($c_repo);
		}
		else
		{
			// If it's in the linked repo list it should have refreshed when starting client
			$test = $supplied;
		}

		if(($c = strrpos($test, '-')) !== false)
		{
			$version = substr($test, ($c + 1));

			if(isset($version[2]) && !isset($version[8]) && pts_strings::string_only_contains($version, (pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL)))
			{
				$test = substr($test, 0, $c);
			}
			else
			{
				$version = null;
			}
		}
		else
		{
			$version = null;
		}

		if($test == null)
			return false;

		foreach($repos as $repo)
		{
			if($repo == 'local')
			{
				if(self::check_only_type_compare($check_only_type, 'test'))
				{
					if(is_file(PTS_TEST_PROFILE_PATH . $repo . '/' . $test . '/test-definition.xml'))
					{
						return $repo . '/' . $test; // ($bind_version ? '-' . $version : null)
					}
					else if(is_file(PTS_TEST_PROFILE_PATH . $repo . '/' . $test . '-' . $version . '/test-definition.xml'))
					{
						return $repo . '/' . $test . '-' . $version; // ($bind_version ? '-' . $version : null)
					}
				}

				if(self::check_only_type_compare($check_only_type, 'suite'))
				{
					if(is_file(PTS_TEST_SUITE_PATH . $repo . '/' . $test . '/suite-definition.xml'))
					{
						return $repo . '/' . $test; // ($bind_version ? '-' . $version : null)
					}
					else if(is_file(PTS_TEST_SUITE_PATH . $repo . '/' . $test . '-' . $version . '/suite-definition.xml'))
					{
						return $repo . '/' . $test . '-' . $version; // ($bind_version ? '-' . $version : null)
					}
				}
			}

			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			if(is_array($repo_index) && isset($repo_index['tests'][$test]) && self::check_only_type_compare($check_only_type, 'test'))
			{
				// The test profile at least exists

				// Looking for a particular test profile version?
				if($version != null)
				{
					if(!in_array($version, $repo_index['tests'][$test]['versions']))
					{
						// Grep to see if the version passed was e.g. 1.3 instead of 1.3.3
						$versions = $repo_index['tests'][$test]['versions'];
						sort($versions);
						foreach(array_reverse($versions) as $check_version)
						{
							if(strstr($check_version, $version) != false)
							{
								$version = $check_version;
								break;
							}
						}
					}

					if(in_array($version, $repo_index['tests'][$test]['versions']))
					{
						pts_openbenchmarking::download_test_profile($repo . '/' . $test . '-' . $version);
						return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
					}
				}
				else
				{
					// Assume to use the latest version unless something else is installed
					$available_versions = $repo_index['tests'][$test]['versions'];
					$version = $available_versions[0]; // the latest version available

					if(PTS_IS_CLIENT && pts_client::current_command() == 'pts_test_run_manager')
					{
						// Check to see if an older version of the test profile is currently installed to run that since no version specified
						foreach($available_versions as $i => $v)
						{
							if(is_file(pts_client::test_install_root_path() . $repo . '/' . $test . '-' . $v . '/pts-install.json'))
							{
								$version = $v;

								if($i > 0)
								{
									// It's not the latest test profile version available
									trigger_error($repo . '/' . $test . ': The latest test profile version available for upgrade is ' . $available_versions[0] . ' but version ' . $version . ' is the latest currently installed.', E_USER_WARNING);
								}
								break;
							}
						}
					}

					pts_openbenchmarking::download_test_profile($repo . '/' . $test . '-' . $version);
					return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
				}
			}
			if(is_array($repo_index) && isset($repo_index['suites'][$test]) && self::check_only_type_compare($check_only_type, 'suite'))
			{
				// The test profile at least exists

				// Looking for a particular test profile version?
				if($version != null)
				{
					if(!in_array($version, $repo_index['suites'][$test]['versions']))
					{
						// Grep to see if the version passed was e.g. 1.3 instead of 1.3.3
						$versions = $repo_index['suites'][$test]['versions'];
						sort($versions);
						foreach(array_reverse($versions) as $check_version)
						{
							if(strstr($check_version, $version) != false)
							{
								$version = $check_version;
								break;
							}
						}
					}

					if(in_array($version, $repo_index['suites'][$test]['versions']))
					{
						pts_openbenchmarking::download_test_suite($repo . '/' . $test . '-' . $version);
						return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
					}
				}
				else
				{
					// Assume to use the latest version
					$version = array_shift($repo_index['suites'][$test]['versions']);
					pts_openbenchmarking::download_test_suite($repo . '/' . $test . '-' . $version);
					return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
				}
			}
		}

		return false;
	}
	public static function upload_test_result(&$object, $return_json_data = false, $prompts = true)
	{
		return pts_openbenchmarking_upload::upload_test_result($object, $return_json_data, $prompts);
	}
	public static function ob_upload_support_available()
	{
		return pts_auto_load_class('pts_openbenchmarking_upload') && pts_config::read_bool_config('PhoronixTestSuite/Options/OpenBenchmarking/AllowResultUploadsToOpenBenchmarking', 'TRUE');
	}
}

?>
