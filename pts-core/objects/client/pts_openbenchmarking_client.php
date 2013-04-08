<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2013, Phoronix Media
	Copyright (C) 2010 - 2013, Michael Larabel

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

class pts_openbenchmarking_client
{
	private static $openbenchmarking_account = false;
	private static $client_settings = null;

	public static function upload_test_result(&$object)
	{
		if($object instanceof pts_test_run_manager)
		{
			$result_file = new pts_result_file($object->get_file_name());
			$local_file_name = $object->get_file_name();
			$results_identifier = $object->get_results_identifier();
		}
		else if($object instanceof pts_result_file)
		{
			$result_file = &$object;
			$local_file_name = $result_file->get_identifier();
			$results_identifier = null;
		}

		// Validate the XML
		// Rely upon server-side validation in case of additions to the spec later on as might be a problem with the JSON addition
		/*
		if($result_file->xml_parser->validate() == false)
		{
			echo PHP_EOL . 'Errors occurred parsing the result file XML.' . PHP_EOL;
			return false;
		}
		*/

		// Ensure the results can be shared
		if(self::result_upload_supported($result_file) == false)
		{
			return false;
		}

		if(pts_network::network_support_available() == false)
		{
			echo PHP_EOL . 'No network support available.' . PHP_EOL;
			return false;
		}

		$composite_xml = $result_file->xml_parser->getXML();
		$system_log_dir = PTS_SAVE_RESULTS_PATH . $result_file->get_identifier() . '/system-logs/';

		if(pts_config::read_bool_config('PhoronixTestSuite/Options/OpenBenchmarking/AlwaysUploadSystemLogs', 'FALSE'))
		{
			$upload_system_logs = true;
		}
		else if(isset(self::$client_settings['UploadSystemLogsByDefault']) && self::$client_settings['UploadSystemLogsByDefault'])
		{
			$upload_system_logs = true;
		}
		else if(is_dir($system_log_dir))
		{
			$upload_system_logs = pts_user_io::prompt_bool_input('Would you like to attach the system logs (lspci, dmesg, lsusb, etc) to the test result', true, 'UPLOAD_SYSTEM_LOGS');
		}

		$system_logs = null;
		$system_logs_hash = null;
		if(is_dir($system_log_dir) && $upload_system_logs)
		{
			$is_valid_log = true;
			$finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;

			foreach(pts_file_io::glob($system_log_dir . '*') as $log_dir)
			{
				if($is_valid_log == false || !is_dir($log_dir))
				{
					$is_valid_log = false;
					break;
				}

				foreach(pts_file_io::glob($log_dir . '/*') as $log_file)
				{
					if(!is_file($log_file))
					{
						$is_valid_log = false;
						break;
					}

					if($finfo && substr(finfo_file($finfo, $log_file), 0, 5) != 'text/')
					{
						$is_valid_log = false;
						break;
					}
				}
			}

			if($is_valid_log)
			{
				$system_logs_zip = pts_client::create_temporary_file();
				pts_compression::zip_archive_create($system_logs_zip, $system_log_dir);

				if(filesize($system_logs_zip) < 2097152)
				{
					// If it's over 2MB, probably too big
					$system_logs = base64_encode(file_get_contents($system_logs_zip));
					$system_logs_hash = sha1($system_logs);
				}
				else
				{
					trigger_error('The systems log attachment is too large to upload to OpenBenchmarking.org.', E_USER_WARNING);
				}

				unlink($system_logs_zip);
			}
		}

		$to_post = array(
			'composite_xml' => base64_encode($composite_xml),
			'composite_xml_hash' => sha1($composite_xml),
			'local_file_name' => $local_file_name,
			'this_results_identifier' => $results_identifier,
			'system_logs_zip' => $system_logs,
			'system_logs_hash' => $system_logs_hash
			);

		if(isset(self::$client_settings['ResultUploadsDefaultDisplayStatus']) && is_numeric(self::$client_settings['ResultUploadsDefaultDisplayStatus']))
		{

			$to_post['display_status'] = self::$client_settings['ResultUploadsDefaultDisplayStatus'];
		}

		$json_response = pts_openbenchmarking::make_openbenchmarking_request('upload_test_result', $to_post);
		$json_response = json_decode($json_response, true);

		if(!is_array($json_response))
		{
			trigger_error('Unhandled Exception', E_USER_ERROR);
			return false;
		}

		if(isset($json_response['openbenchmarking']['upload']['error']))
		{
			trigger_error($json_response['openbenchmarking']['upload']['error'], E_USER_ERROR);
		}
		if(isset($json_response['openbenchmarking']['upload']['url']))
		{
			echo PHP_EOL . 'Results Uploaded To: ' . $json_response['openbenchmarking']['upload']['url'] . PHP_EOL;
			pts_module_manager::module_process('__event_openbenchmarking_upload', $json_response);
		}
		//$json['openbenchmarking']['upload']['id']

		if(isset(self::$client_settings['RemoveLocalResultsOnUpload']) && self::$client_settings['RemoveLocalResultsOnUpload'] && $local_file_name != null)
		{
			pts_client::remove_saved_result_file($local_file_name);
		}

		return isset($json_response['openbenchmarking']['upload']['url']) ? $json_response['openbenchmarking']['upload']['url'] : false;
	}
	public static function init_account($openbenchmarking)
	{
		if(isset($openbenchmarking['user_name']) && isset($openbenchmarking['communication_id']) && isset($openbenchmarking['sav']))
		{
			if(IS_FIRST_RUN_TODAY)
			{
				// Might as well make sure OpenBenchmarking.org account has the latest system info
				// But don't do it everytime to preserve bandwidth
				$openbenchmarking['s_s'] = base64_encode(phodevi::system_software(true));
				$openbenchmarking['s_h'] = base64_encode(phodevi::system_hardware(true));
			}

			$return_state = pts_openbenchmarking::make_openbenchmarking_request('account_verify', $openbenchmarking);
			$json = json_decode($return_state, true);

			if(isset($json['openbenchmarking']['account']['valid']))
			{
				// The account is valid
				self::$openbenchmarking_account = $openbenchmarking;
				self::$client_settings = $json['openbenchmarking']['account']['settings'];
			}
		}
	}
	public static function auto_upload_results()
	{
		return isset(self::$client_settings['AutoUploadResults']) && self::$client_settings['AutoUploadResults'];
	}
	public static function override_client_setting($key, $value)
	{
		self::$client_settings[$key] = $value;
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

		if(is_array(self::$openbenchmarking_account))
		{
			$to_post = array_merge($to_post, self::$openbenchmarking_account);
		}

		return pts_network::http_upload_via_post($url, $to_post);
	}
	protected static function result_upload_supported(&$result_file)
	{
		foreach($result_file->get_result_objects() as $result_object)
		{
			$test_profile = new pts_test_profile($result_object->test_profile->get_identifier());

			if($test_profile->allow_results_sharing() == false)
			{
				echo PHP_EOL . $result_object->test_profile->get_identifier() . ' does not allow test results to be uploaded.' . PHP_EOL . PHP_EOL;
				return false;
			}
		}

		return true;
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

			$index_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . '.index';

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
			}

			$server_index = pts_openbenchmarking::make_openbenchmarking_request('repo_index', array('repo' => $repo_name));

			if(json_decode($server_index) != false)
			{
				file_put_contents($index_file, $server_index);
			}
			else if(is_file('/var/cache/phoronix-test-suite/openbenchmarking.org/' . $repo_name . '.index'))
			{
				copy('/var/cache/phoronix-test-suite/openbenchmarking.org/' . $repo_name . '.index', $index_file);
			}

			if(!is_file($index_file))
			{
				static $reported_read_failure_notice;

				if(!isset($reported_read_failure_notice[$repo_name]))
				{
					trigger_error('Failed To Fetch OpenBenchmarking.org Repository Data: ' . $repo_name, E_USER_WARNING);
					$reported_read_failure_notice[$repo_name] = true;
				}
			}
		}
	}
	public static function fetch_repository_changelog($repo_name)
	{
		$index_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . '.changes';

		if(!is_file($index_file) || filemtime($index_file) < (time() - 86400))
		{
			// Refresh the repository change-log just once a day should be fine
			$server_index = pts_openbenchmarking::make_openbenchmarking_request('repo_changes', array('repo' => $repo_name));

			if(json_decode($server_index) != false)
			{
				file_put_contents($index_file, $server_index);
			}
		}

		return is_file($index_file) ? json_decode(file_get_contents($index_file), true) : false;
	}
	public static function download_test_profile($qualified_identifier)
	{
		if(is_file(PTS_TEST_PROFILE_PATH . $qualified_identifier . '/test-definition.xml'))
		{
			return true;
		}

		$file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $qualified_identifier . '.zip';

		$hash_json = pts_openbenchmarking::make_openbenchmarking_request('test_hash', array('i' => $qualified_identifier));
		$hash_json = json_decode($hash_json, true);
		$hash_check = isset($hash_json['openbenchmarking']['test']['hash']) ? $hash_json['openbenchmarking']['test']['hash'] : null;  // should also check for ['openbenchmarking']['test']['error'] problems

		if(!is_file($file))
		{
			$test_profile = pts_openbenchmarking::make_openbenchmarking_request('download_test', array('i' => $qualified_identifier));

			if($test_profile != null && ($hash_check == null || $hash_check == sha1($test_profile)))
			{
				// save it
				file_put_contents($file, $test_profile);
				$hash_check = null;
			}
			else if(is_file('/var/cache/phoronix-test-suite/openbenchmarking.org/' . $qualified_identifier . '.zip') && ($hash_check == null || sha1_file('/var/cache/phoronix-test-suite/openbenchmarking.org/' . $qualified_identifier . '.zip') == $hash_check))
			{
				copy('/var/cache/phoronix-test-suite/openbenchmarking.org/' . $qualified_identifier . '.zip', $file);
			}
			else if(PTS_IS_CLIENT && $test_profile === false)
			{
				trigger_error('Network support is needed to obtain ' . $qualified_identifier . ' data.' . PHP_EOL, E_USER_ERROR);
				return false;
			}
		}

		if(!is_file(PTS_TEST_PROFILE_PATH . $qualified_identifier . '/test-definition.xml') && is_file($file) && ($hash_check == null || sha1_file($file) == $hash_check))
		{
			// extract it
			pts_file_io::mkdir(PTS_TEST_PROFILE_PATH . dirname($qualified_identifier));
			pts_file_io::mkdir(PTS_TEST_PROFILE_PATH . $qualified_identifier);
			return pts_compression::zip_archive_extract($file, PTS_TEST_PROFILE_PATH . $qualified_identifier);
		}

		return false;
	}
	public static function download_test_suite($qualified_identifier)
	{
		if(is_file(PTS_TEST_SUITE_PATH . $qualified_identifier . '/suite-definition.xml'))
		{
			return true;
		}

		$file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $qualified_identifier . '.zip';

		$hash_json = pts_openbenchmarking::make_openbenchmarking_request('suite_hash', array('i' => $qualified_identifier));
		$hash_json = json_decode($hash_json, true);
		$hash_check = isset($hash_json['openbenchmarking']['suite']['hash']) ? $hash_json['openbenchmarking']['suite']['hash'] : null;  // should also check for ['openbenchmarking']['suite']['error'] problems

		if(!is_file($file))
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
			else if(PTS_IS_CLIENT && $test_suite === false)
			{
				trigger_error('Network support is needed to obtain ' . $qualified_identifier . ' data.' . PHP_EOL, E_USER_ERROR);
				return false;
			}
		}

		if(!is_file(PTS_TEST_SUITE_PATH . $qualified_identifier . '/suite-definition.xml') && ($hash_check == null || (is_file($file) && sha1_file($file) == $hash_check)))
		{
			// extract it
			pts_file_io::mkdir(PTS_TEST_SUITE_PATH . dirname($qualified_identifier));
			pts_file_io::mkdir(PTS_TEST_SUITE_PATH . $qualified_identifier);
			return pts_compression::zip_archive_extract($file, PTS_TEST_SUITE_PATH . $qualified_identifier);
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
				pts_openbenchmarking_client::refresh_repository_lists($repos);
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
						pts_openbenchmarking_client::download_test_profile($repo . '/' . $test . '-' . $version);
						return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
					}
				}
				else
				{
					// Assume to use the latest version unless something else is installed
					$available_versions = $repo_index['tests'][$test]['versions'];
					$version = $available_versions[0]; // the latest version available

					if((pts_c::$test_flags & pts_c::is_run_process))
					{
						// Check to see if an older version of the test profile is currently installed
						foreach($available_versions as $i => $v)
						{
							if(is_file(pts_client::test_install_root_path() . $repo . '/' . $test . '-' . $v . '/pts-install.xml'))
							{
								$version = $v;

								if($i > 0 && (pts_c::$test_flags ^ pts_c::batch_mode))
								{
									// It's not the latest test profile version available
									trigger_error($repo . '/' . $test . ': The latest test profile version available for upgrade is ' . $available_versions[0] . ' but version ' . $version . ' is the latest currently installed.', E_USER_WARNING);
								}
								break;
							}
						}
					}

					pts_openbenchmarking_client::download_test_profile($repo . '/' . $test . '-' . $version);
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
						pts_openbenchmarking_client::download_test_suite($repo . '/' . $test . '-' . $version);
						return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
					}
				}
				else
				{
					// Assume to use the latest version
					$version = array_shift($repo_index['suites'][$test]['versions']);
					pts_openbenchmarking_client::download_test_suite($repo . '/' . $test . '-' . $version);
					return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
				}
			}
		}

		return false;
	}
	public static function available_tests($download_tests = true)
	{
		$available_tests = array();

		foreach(self::linked_repositories() as $repo)
		{
			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			if(isset($repo_index['tests']) && is_array($repo_index['tests']))
			{
				foreach(array_keys($repo_index['tests']) as $identifier)
				{
					if($download_tests && pts_network::network_support_available())
					{
						$version = array_shift($repo_index['tests'][$identifier]['versions']);
						if(self::download_test_profile($repo . '/' . $identifier . '-' . $version) == false)
						{
							continue;
						}
					}

					array_push($available_tests, $repo . '/' . $identifier);
				}
			}
		}

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
					if($download_suites && pts_network::network_support_available())
					{
						$version = array_shift($repo_index['suites'][$identifier]['versions']);
						if(self::download_test_suite($repo . '/' . $identifier . '-' . $version) == false)
						{
							continue;
						}
					}
					array_push($available_suites, $repo . '/' . $identifier);
				}
			}
		}

		return $available_suites;
	}
	public static function user_name()
	{
		return isset(self::$openbenchmarking_account['user_name']) ? self::$openbenchmarking_account['user_name'] : false;
	}
	public static function upload_usage_data($task, $data)
	{
		switch($task)
		{
			case 'test_install':
				list($test_install, $time_elapsed) = $data;
				$upload_data = array('test_identifier' => $test_install->test_profile->get_identifier(), 'test_version' => $test_install->test_profile->get_test_profile_version(), 'elapsed_time' => $time_elapsed);
				pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . 'extern/statistics/report-test-install.php', $upload_data);
				break;
			case 'test_complete':
				list($test_result, $time_elapsed) = $data;
				$upload_data = array('test_identifier' => $test_result->test_profile->get_identifier(), 'test_version' => $test_result->test_profile->get_test_profile_version(), 'elapsed_time' => $time_elapsed);
				pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . 'extern/statistics/report-test-completion.php', $upload_data);
				break;
			case 'test_install_failure':
				list($test_install, $error) = $data;
				$upload_data = array('test_identifier' => $test_install->test_profile->get_identifier(), 'error' => $error, 'os' => phodevi::read_property('system', 'vendor-identifier'));
				pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . 'extern/statistics/report-test-install-failure.php', $upload_data);
				break;
		}
	}
	public static function upload_hwsw_data($to_report)
	{
		if(!defined('PTS_GSID'))
		{
			return false;
		}

		foreach($to_report as $component => &$value)
		{
			if(empty($value))
			{
				unset($to_report[$component]);
				continue;
			}

			$value = $component . '=' . $value;
		}

		$upload_data = array('report_hwsw' => implode(';', $to_report), 'gsid' => PTS_GSID);
		pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . 'extern/statistics/report-installed-hardware-software.php', $upload_data);
	}
	public static function upload_pci_data($to_report)
	{
		if(!defined('PTS_GSID'))
		{
			return false;
		}

		if(!is_array($to_report))
		{
			return false;
		}

		$to_report = base64_encode(serialize($to_report));

		$upload_data = array('report_pci_data' => $to_report, 'gsid' => PTS_GSID);
		pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . 'extern/statistics/report-pci-data.php', $upload_data);
	}
	public static function upload_usb_data($to_report)
	{
		if(!defined('PTS_GSID'))
		{
			return false;
		}

		if(!is_array($to_report))
		{
			return false;
		}

		$to_report = base64_encode(serialize($to_report));

		$upload_data = array('report_usb_data' => $to_report, 'gsid' => PTS_GSID);
		pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . 'extern/statistics/report-usb-data.php', $upload_data);
	}
	public static function request_gsid()
	{
		$payload = array(
			'client_version' => PTS_VERSION,
			'client_os' => phodevi::read_property('system', 'vendor-identifier')
			);
		$json = pts_openbenchmarking::make_openbenchmarking_request('request_gsid', $payload);
		$json = json_decode($json, true);

		return isset($json['openbenchmarking']['gsid']) ? $json['openbenchmarking']['gsid'] : false;
	}
	public static function update_gsid()
	{
		$payload = array(
			'client_version' => PTS_VERSION,
			'client_os' => phodevi::read_property('system', 'vendor-identifier')
			);
		pts_openbenchmarking::make_openbenchmarking_request('update_gsid', $payload);
	}
	public static function retrieve_gsid()
	{
		// If the GSID_E and GSID_P are not known due to being from an old client
		$json = pts_openbenchmarking::make_openbenchmarking_request('retrieve_gsid', array());
		$json = json_decode($json, true);

		return isset($json['openbenchmarking']['gsid']) ? $json['openbenchmarking']['gsid'] : false;
	}
	public static function linked_repositories()
	{
		$repos = array('local', 'pts');

		if(self::user_name() != false)
		{
			array_push($repos, self::user_name());
		}

		return $repos;
	}
}

?>
