<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2018, Phoronix Media
	Copyright (C) 2010 - 2018, Michael Larabel

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
	public static function valid_user_name()
	{
		$invalid_users = array('pts', 'phoronix', 'local', 'official');
		// TODO: finish function
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

		if(strlen($id) == 22)
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
			// OpenSSL seems to have problems on OpenIndiana at least, TODO: investigate
			// Using a proxy seems to have problems for HTTPS
			// TODO XXX
			//$host = ((extension_loaded('openssl') && getenv('NO_OPENSSL') == false && php_uname('s') == 'Linux' && (!PTS_IS_CLIENT || !pts_network::is_proxy_setup())) ? 'https://' : 'http://') . 'openbenchmarking.org/';
			$host = 'http://openbenchmarking.org/';
		}

		return $host;
	}
	public static function report_repository_index_updates($repo, $old_index, $new_index)
	{
		if(isset($new_index['tests']) && isset($old_index['tests']) && $new_index['tests'] != $old_index['tests'])
		{
			$new_test_count = count($new_index['tests']);
			echo pts_client::cli_colored_text('Updated OpenBenchmarking.org Repository Index', 'green', true) . PHP_EOL;
			echo pts_client::cli_colored_text($repo . ': ' . count($new_index['tests']) . ' Distinct Tests, ' . count($new_index['suites']) . ' Suites', 'green', true) . PHP_EOL;
			$table = array();
			foreach(array_keys($new_index['tests']) as $test)
			{
				if(!isset($old_index['tests'][$test]))
				{
					$table[] = array(pts_client::cli_just_bold('New Test Available: '), $repo . '/' . $test, pts_client::cli_colored_text('v' . array_shift($new_index['tests'][$test]['versions']), 'gray'));
				}
				else if($new_index['tests'][$test]['versions'] != $old_index['tests'][$test]['versions'])
				{
					$version_diff = array_diff($new_index['tests'][$test]['versions'], $old_index['tests'][$test]['versions']);
					if(!empty($version_diff))
					{
						$table[] = array(pts_client::cli_just_bold('Updated Test Available: '), $repo . '/' . $test, pts_client::cli_colored_text('v' . array_shift($version_diff), 'gray'));
					}
				}
			}
			echo pts_user_io::display_text_table($table) . PHP_EOL;
		}
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

				// Refreshing the indexes once every few days should be suffice
				// Refresh approximately every three days by default
				$index_cache_ttl = 3;
				if(PTS_IS_CLIENT && ($config_ttl = pts_config::read_user_config('PhoronixTestSuite/Options/OpenBenchmarking/IndexCacheTTL')))
				{
					if($config_ttl === 0)
					{
						// if the value is 0, only rely upon manual refreshes
						continue;
					}
					else if(is_numeric($config_ttl) && $config_ttl >= 1)
					{
						$index_cache_ttl = $config_ttl;
					}
				}

				if($generated_time > (time() - (86400 * $index_cache_ttl)) && $force_refresh == false && (!defined('FIRST_RUN_ON_PTS_UPGRADE') || FIRST_RUN_ON_PTS_UPGRADE == false))
				{
					// The index is new enough
					continue;
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
					trigger_error('Failed To Fetch OpenBenchmarking.org Repository Data: ' . $repo_name . '. If this issue persists, contact support@phoronix-test-suite.com.', E_USER_WARNING);
					$reported_read_failure_notice[$repo_name] = true;
				}
			}
		}
	}
	public static function openbenchmarking_has_refreshed()
	{
		return self::$openbenchmarking_index_refreshed;
	}
	public static function linked_repositories()
	{
		$repos = array('local', 'pts', 'system');

		if(PTS_IS_CLIENT && phodevi::is_windows())
		{
			// Various windows tests for compatibility where there isn't mainline support in the test profile otherwise
			array_unshift($repos, 'windows');
		}

		if(PTS_IS_CLIENT && pts_openbenchmarking_client::user_name() != false)
		{
			$repos[] = pts_openbenchmarking_client::user_name();
		}
		$on_system_indexes = glob(PTS_OPENBENCHMARKING_SCRATCH_PATH . '*.index');
		foreach($on_system_indexes as $index)
		{
			$index = basename($index, '.index');
			pts_arrays::unique_push($repos, $index);
		}

		return $repos;
	}
	public static function make_openbenchmarking_request($request, $post = array())
	{
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

		return pts_network::http_upload_via_post($url, $to_post);
	}
	public static function is_repository($repo_name)
	{
		return is_file(PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . '.index');
	}
	public static function read_repository_index($repo_name, $do_decode = true)
	{
		static $caches;
		static $last_cache_times;

		if($do_decode && isset($caches[$repo_name]) && $last_cache_times[$repo_name] > (time() - 60))
		{
			return $caches[$repo_name];
		}

		$index_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . '.index';
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
				$hash_json = pts_openbenchmarking::make_openbenchmarking_request('test_hash', array('i' => $qualified_identifier));
				$hash_json = json_decode($hash_json, true);
				$hash_check = isset($hash_json['openbenchmarking']['test']['hash']) ? $hash_json['openbenchmarking']['test']['hash'] : null;  // should also check for ['openbenchmarking']['test']['error'] problems

				$test_profile = pts_openbenchmarking::make_openbenchmarking_request('download_test', array('i' => $qualified_identifier));

				if($test_profile != null && ($hash_check == null || $hash_check == sha1($test_profile)))
				{
					// save it
					file_put_contents($file, $test_profile);
					$hash_check = null;
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
					trigger_error('Unable to obtain ' . $qualified_identifier . ' from OpenBenchmarking.org. If this issue persists, contact support@phoronix-test-suite.com.' . PHP_EOL, E_USER_ERROR);
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
					$zip_file = tempnam(sys_get_temp_dir(), 'phoromatic-zip');
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
			$cache = pts_network::http_get_contents('http://' . $archived_server['ip'] . ':' . $archived_server['http_port'] . '/openbenchmarking-cache.php?' . $type_request . '&repo=' . $repo . '&test=' . $test);

			if(!empty($cache))
			{
				return $cache;
			}
		}

		return null;
	}
	public static function available_tests($download_tests = true, $all_versions = false, $append_versions = false, $show_deprecated_tests = false)
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

					if($all_versions)
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

						$available_tests[] = $repo . '/' . $identifier . ($append_versions ? '-' . $version : null);
					}
				}
			}
		}
		$available_tests = array_unique($available_tests);

		return $available_tests;
	}
	public static function available_suites($download_suites = true)
	{
		$available_suites = array();

		foreach(self::linked_repositories() as $repo)
		{
			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			if(isset($repo_index['suites']) && is_array($repo_index['suites']))
			{
				foreach(array_keys($repo_index['suites']) as $identifier)
				{
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

		if(pts_network::internet_support_available())
		{
			$hash_json = pts_openbenchmarking::make_openbenchmarking_request('suite_hash', array('i' => $qualified_identifier));
			$hash_json = json_decode($hash_json, true);
			$hash_check = isset($hash_json['openbenchmarking']['suite']['hash']) ? $hash_json['openbenchmarking']['suite']['hash'] : null;  // should also check for ['openbenchmarking']['suite']['error'] problems
		}
		else
		{
			$hash_check = null;
		}

		if(!is_file($file))
		{
			if(pts_network::internet_support_available())
			{
				$test_suite = pts_openbenchmarking::make_openbenchmarking_request('download_suite', array('i' => $qualified_identifier));

				if($test_suite != null && ($hash_check == null || $hash_check == sha1($test_suite)))
				{
					// save it
					file_put_contents($file, $test_suite);
					$hash_check = null;
				}
				else if(is_file('/var/cache/phoronix-test-suite/openbenchmarking.org/' . $qualified_identifier . '.zip') && ($hash_check == null || sha1_file('/var/cache/phoronix-test-suite/openbenchmarking.org/' . $qualified_identifier . '.zip') == $hash_check))
				{
					copy('/var/cache/phoronix-test-suite/openbenchmarking.org/' . $qualified_identifier . '.zip', $file);
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
					trigger_error('Unable to obtain ' . $qualified_identifier . ' from OpenBenchmarking.org.  If this issue persists, contact support@phoronix-test-suite.com.' . PHP_EOL, E_USER_ERROR);
				}
				return false;
			}
		}

		if(!is_file($download_location . $qualified_identifier . '/suite-definition.xml') && is_file($file) && ($hash_check == null || (is_file($file) && sha1_file($file) == $hash_check)))
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

			// TODO: functionalize this and read against types.xsd
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
						// Check to see if an older version of the test profile is currently installed to ru nthat since no version specified
						foreach($available_versions as $i => $v)
						{
							if(is_file(pts_client::test_install_root_path() . $repo . '/' . $test . '-' . $v . '/pts-install.xml'))
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
		return pts_openbenchmarking_client::upload_test_result($object, $return_json_data, $prompts);
	}
}

?>
