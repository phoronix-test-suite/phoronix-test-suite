<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2017, Phoronix Media
	Copyright (C) 2010 - 2017, Michael Larabel

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

	public static function upload_test_result(&$object, $return_json_data = false, $prompts = true)
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

		// Ensure the results can be shared
		if(self::result_upload_supported($result_file) == false)
		{
			return false;
		}

		if(pts_network::internet_support_available() == false)
		{
			echo PHP_EOL . 'No network support available.' . PHP_EOL;
			return false;
		}

		$composite_xml = $result_file->get_xml();
		$system_log_dir = PTS_SAVE_RESULTS_PATH . $result_file->get_identifier() . '/system-logs/';
		$upload_system_logs = false;

		if(is_dir($system_log_dir))
		{
			if(pts_config::read_bool_config('PhoronixTestSuite/Options/OpenBenchmarking/AlwaysUploadSystemLogs', 'FALSE'))
			{
				$upload_system_logs = true;
			}
			else if(isset(self::$client_settings['UploadSystemLogsByDefault']))
			{
				$upload_system_logs = self::$client_settings['UploadSystemLogsByDefault'];
			}
			else if(is_dir($system_log_dir))
			{
				if($prompts == false)
				{
					$upload_system_logs = true;
				}
				else
				{
					$upload_system_logs = pts_user_io::prompt_bool_input('Would you like to attach the system logs (lspci, dmesg, lsusb, etc) to the test result', -1, 'UPLOAD_SYSTEM_LOGS');
				}
			}
		}

		$system_logs = null;
		$system_logs_hash = null;
		if($upload_system_logs)
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
				$system_logs_zip = pts_client::create_temporary_file('.zip');
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

		$composite_xml_hash = sha1($composite_xml);
		$composite_xml_type = 'composite_xml';

		// Compress the result file XML if it's big
		if(isset($composite_xml[40000]) && function_exists('bzcompress'))
		{
			$composite_xml_bz = bzcompress($composite_xml);

			if($composite_xml_bz != false)
			{
				$composite_xml = $composite_xml_bz;
				$composite_xml_type = 'composite_xml_bz';
			}
		}
		else if(isset($composite_xml[40000]) && function_exists('gzdeflate'))
		{
			$composite_xml_gz = gzdeflate($composite_xml);

			if($composite_xml_gz != false)
			{
				$composite_xml = $composite_xml_gz;
				$composite_xml_type = 'composite_xml_gz';
			}
		}
		$to_post = array(
			$composite_xml_type => base64_encode($composite_xml),
			'composite_xml_hash' => $composite_xml_hash,
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
		if(!is_array($json_response) && !empty($system_logs))
		{
			// Sometimes OpenBenchmarking has issues with large result files, so for now try uploading again with no logs
			// XXX  TODO figure out why OB sometimes fails with large result files
			$to_post['system_logs_zip'] = null;
			$to_post['system_logs_hash'] = null;
			$json_response = pts_openbenchmarking::make_openbenchmarking_request('upload_test_result', $to_post);
			$json_response = json_decode($json_response, true);
		}


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
			echo PHP_EOL . pts_client::cli_just_bold('Results Uploaded To: ') . $json_response['openbenchmarking']['upload']['url'] . PHP_EOL;
			pts_module_manager::module_process('__event_openbenchmarking_upload', $json_response);
		}
		//$json['openbenchmarking']['upload']['id']

		if(isset(self::$client_settings['RemoveLocalResultsOnUpload']) && self::$client_settings['RemoveLocalResultsOnUpload'] && $local_file_name != null)
		{
			pts_client::remove_saved_result_file($local_file_name);
		}

		if($return_json_data)
		{
			return isset($json_response['openbenchmarking']['upload']) ? $json_response['openbenchmarking']['upload'] : false;
		}

		return isset($json_response['openbenchmarking']['upload']['url']) ? $json_response['openbenchmarking']['upload']['url'] : false;
	}
	public static function recently_updated_tests($limit = -1)
	{
		$available_tests = array();

		foreach(pts_openbenchmarking::linked_repositories() as $repo)
		{
			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			if(isset($repo_index['tests']) && is_array($repo_index['tests']))
			{
				foreach(array_keys($repo_index['tests']) as $identifier)
				{
					if($repo_index['tests'][$identifier]['title'] == null)
					{
						continue;
					}

					$version = array_shift($repo_index['tests'][$identifier]['versions']);
					$update_time = $repo_index['tests'][$identifier]['last_updated'];
					$available_tests[$update_time] = $repo . '/' . $identifier . '-' . $version;
				}
			}
		}

		krsort($available_tests);

		if($limit > 0)
		{
			$available_tests = array_slice($available_tests, 0, $limit);
		}

		return $available_tests;
	}
	public static function popular_tests($limit = -1, $test_type = null)
	{
		$available_tests = array();

		foreach(pts_openbenchmarking::linked_repositories() as $repo)
		{
			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			if(isset($repo_index['tests']) && is_array($repo_index['tests']))
			{
				foreach(array_keys($repo_index['tests']) as $identifier)
				{
					if($repo_index['tests'][$identifier]['title'] == null)
					{
						continue;
					}

					$popularity = $repo_index['tests'][$identifier]['popularity'];

					if($popularity < 1 || ($test_type != null && $repo_index['tests'][$identifier]['test_type'] != $test_type))
					{
						continue;
					}

					$available_tests[$repo . '/' . $identifier] = $popularity;
				}
			}
		}

		asort($available_tests);

		if($limit > 0)
		{
			$available_tests = array_slice($available_tests, 0, $limit);
		}

		return array_keys($available_tests);
	}
	public static function search_tests($search, $test_titles_only = true)
	{
		$matching_tests = array();

		foreach(pts_openbenchmarking::linked_repositories() as $repo)
		{
			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			if(isset($repo_index['tests']) && is_array($repo_index['tests']))
			{
				foreach(array_keys($repo_index['tests']) as $identifier)
				{
					if(stripos($identifier, $search) !== false || stripos($repo_index['tests'][$identifier]['title'], $search) !== false)
					{
						$matching_tests[] = $repo . '/' . $identifier;
					}
					else if($test_titles_only == false && (stripos(implode(' ', $repo_index['tests'][$identifier]['internal_tags']), $search) !== false || stripos($repo_index['tests'][$identifier]['test_type'], $search) !== false || stripos($repo_index['tests'][$identifier]['description'], $search) !== false))
					{
						$matching_tests[] = $repo . '/' . $identifier;
					}
				}
			}
		}

		return $matching_tests;
	}
	public static function tests_available()
	{
		$test_count = 0;

		foreach(pts_openbenchmarking::linked_repositories() as $repo)
		{
			$repo_index = pts_openbenchmarking::read_repository_index($repo);

			if(isset($repo_index['tests']) && is_array($repo_index['tests']))
			{
				$test_count += count($repo_index['tests']);
			}
		}

		return $test_count;
	}
	public static function init_account($openbenchmarking, $settings)
	{
		if(isset($openbenchmarking['user_name']) && isset($openbenchmarking['communication_id']) && isset($openbenchmarking['sav']))
		{
			if(IS_FIRST_RUN_TODAY && pts_network::internet_support_available())
			{
				// Might as well make sure OpenBenchmarking.org account has the latest system info
				// But don't do it everytime to preserve bandwidth
				$openbenchmarking['s_s'] = base64_encode(phodevi::system_software(true));
				$openbenchmarking['s_h'] = base64_encode(phodevi::system_hardware(true));

				$return_state = pts_openbenchmarking::make_openbenchmarking_request('account_verify', $openbenchmarking);
				$json = json_decode($return_state, true);

				if(isset($json['openbenchmarking']['account']['valid']))
				{
					// The account is valid
					self::$openbenchmarking_account = $openbenchmarking;
					self::$client_settings = $json['openbenchmarking']['account']['settings'];
					pts_storage_object::set_in_file(PTS_CORE_STORAGE, 'openbenchmarking_account_settings', $json['openbenchmarking']['account']['settings']);
				}
				else
				{
					pts_storage_object::set_in_file(PTS_CORE_STORAGE, 'openbenchmarking', false);
					trigger_error('Invalid OpenBenchmarking.org account supplied, please re-login.', E_USER_ERROR);
				}
			}
			else
			{
				self::$openbenchmarking_account = $openbenchmarking;
				self::$client_settings = $settings;
			}
		}
	}
	public static function get_openbenchmarking_account()
	{
		return self::$openbenchmarking_account;
	}
	public static function auto_upload_results()
	{
		return isset(self::$client_settings['AutoUploadResults']) && self::$client_settings['AutoUploadResults'];
	}
	public static function override_client_setting($key, $value)
	{
		self::$client_settings[$key] = $value;
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
	public static function read_repository_test_profile_attribute($test_profile, $attribute)
	{
		list($repo, $tp) = explode('/', $test_profile);
		$tp = substr($tp, 0, strrpos($tp, '-'));
		$repo_index = pts_openbenchmarking::read_repository_index($repo);

		return isset($repo_index['tests'][$tp][$attribute]) ? $repo_index['tests'][$tp][$attribute] : null;
	}
	public static function popular_openbenchmarking_results()
	{
		$index_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . 'popular.results';

		if(!is_file($index_file) || filemtime($index_file) < (time() - 1800))
		{
			// Refresh the repository change-log just once a day should be fine
			$server_index = pts_openbenchmarking::make_openbenchmarking_request('interesting_results');

			if(json_decode($server_index) != false)
			{
				file_put_contents($index_file, $server_index);
			}
		}

		$results = is_file($index_file) ? json_decode(file_get_contents($index_file), true) : false;

		return $results ? $results['results'] : false;
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
	public static function fetch_repository_test_profile_changelog($test_profile_identifier_sans_version)
	{
		$server_index = pts_openbenchmarking::make_openbenchmarking_request('repo_changes', array('test_profile' => $test_profile_identifier_sans_version));
		return ($j = json_decode($server_index, true)) != false ? $j : false;
	}
	public static function fetch_repository_changelog_full($repo_name)
	{
		// Fetch the complete OpenBenchmarking.org change-log for this user/repository
		$server_index = pts_openbenchmarking::make_openbenchmarking_request('repo_changes', array('repo' => $repo_name, 'full' => 'full'));

		if(($j = json_decode($server_index, true)) != false)
		{
			return $j;
		}

		return false;
	}
	public static function user_name()
	{
		return isset(self::$openbenchmarking_account['user_name']) ? self::$openbenchmarking_account['user_name'] : false;
	}
	public static function upload_usage_data($task, $data)
	{
		if(!pts_network::internet_support_available())
		{
			return false;
		}

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
	public static function request_gsid()
	{
		if(!pts_network::internet_support_available())
		{
			return false;
		}

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
		if(!pts_network::internet_support_available())
		{
			return false;
		}

		$payload = array(
			'client_version' => PTS_VERSION,
			'client_os' => phodevi::read_property('system', 'vendor-identifier')
			);
		pts_openbenchmarking::make_openbenchmarking_request('update_gsid', $payload);
	}
	public static function retrieve_gsid()
	{
		if(!pts_network::internet_support_available())
		{
			return false;
		}

		// If the GSID_E and GSID_P are not known due to being from an old client
		$json = pts_openbenchmarking::make_openbenchmarking_request('retrieve_gsid', array());
		$json = json_decode($json, true);

		return isset($json['openbenchmarking']['gsid']) ? $json['openbenchmarking']['gsid'] : false;
	}
}

?>
