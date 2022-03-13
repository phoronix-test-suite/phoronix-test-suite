<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2020, Phoronix Media
	Copyright (C) 2010 - 2020, Michael Larabel

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
	public static $client_settings = null;

	public static function upload_test_result(&$object, $return_json_data = false, $prompts = true)
	{
		if(pts_openbenchmarking::ob_upload_support_available() == false)
		{
			trigger_error('No OpenBenchmarking.org upload support available.', E_USER_WARNING);
			return false;
		}

		return pts_openbenchmarking_upload::upload_test_result($object, $return_json_data, $prompts);
	}
	public static function upload_usage_data($task, $data)
	{
		if(pts_openbenchmarking::ob_upload_support_available() == false)
		{
			//trigger_error('No OpenBenchmarking.org upload support available.', E_USER_WARNING);
			return false;
		}
		return pts_openbenchmarking_upload::upload_usage_data($task, $data);
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
	public static function recently_added_tests($limit = -1)
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
					$add_time = $repo_index['tests'][$identifier]['first_added'];
					$available_tests[$add_time] = $repo . '/' . $identifier . '-' . $version;
				}
			}
		}

		krsort($available_tests);

		if($limit > 0)
		{
			$available_tests = array_slice($available_tests, 0, $limit, true);
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
	public static function read_repository_test_profile_attribute($test_profile, $attribute)
	{
		if(empty($test_profile))
		{
			return;
		}

		list($repo, $tp) = explode('/', $test_profile);
		if(($x = strrpos($tp, '-')) && pts_strings::is_version(substr($tp, ($x + 1))))
		{
			// Remove version postfix if appended
			$tp = substr($tp, 0, $x);
		}

		$repo_index = pts_openbenchmarking::read_repository_index($repo);

		return isset($repo_index['tests'][$tp][$attribute]) ? $repo_index['tests'][$tp][$attribute] : null;
	}
	public static function test_profile_newer_minor_version_available(&$test_profile)
	{
		$test_identifier = $test_profile->get_identifier(false);
		$test_current_version = $test_profile->get_test_profile_version();
		$versions = pts_openbenchmarking_client::read_repository_test_profile_attribute($test_identifier, 'versions');

		if($versions && ($x = strrpos($test_current_version, '.')) !== false)
		{
			$current_version_short = substr($test_current_version, 0, $x);
			$current_version_minor_number = substr($test_current_version, ($x + 1));
			$new_minor_version = false;
			foreach($versions as $v)
			{
				if(substr($v, 0, strlen($current_version_short)) == $current_version_short)
				{
					if(substr($v, (strrpos($v, '.') + 1)) > $current_version_minor_number)
					{
						$new_minor_version = $v;
						break;
					}
				}
			}

			if($new_minor_version)
			{
				pts_openbenchmarking::download_test_profile($test_identifier . '-' . $new_minor_version);
				$tp = new pts_test_profile($test_identifier . '-' . $new_minor_version);
				if($tp->get_test_profile_version() == $new_minor_version)
				{
					// can read version correctly, thus test is there
					return $tp;
				}
			}
		}

		return false;
	}
	public static function read_repository_test_suite_attribute($test_profile, $attribute)
	{
		list($repo, $ts) = explode('/', $test_profile);
		if(($x = strrpos($ts, '-')))
		{
			$ts = substr($ts, 0, $x);
		}
		$repo_index = pts_openbenchmarking::read_repository_index($repo);

		return isset($repo_index['suites'][$ts][$attribute]) ? $repo_index['suites'][$ts][$attribute] : null;
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
	public static function compare_test_json_download_counts($a, $b)
	{
		$a = $a['downloads'];
		$b = $b['downloads'];

		if($a == $b)
		{
			return 0;
		}

		return ($a > $b) ? -1 : 1;
	}
	public static function compare_test_last_updated($a, $b)
	{
		$a = $a['last_updated'];
		$b = $b['last_updated'];

		if($a == $b)
		{
			return 0;
		}

		return ($a > $b) ? -1 : 1;
	}
	public static function most_popular_tests($limit = 10)
	{
		$only_show_available_cached_tests = pts_network::internet_support_available() == false;
		$tests = array();

		foreach(pts_openbenchmarking::available_tests(false, false, false, false, $only_show_available_cached_tests) as $identifier)
		{
			$repo = substr($identifier, 0, strpos($identifier, '/'));
			$id = substr($identifier, strlen($repo) + 1);
			$repo_index = pts_openbenchmarking::read_repository_index($repo);
			if((!empty($repo_index['tests'][$id]['supported_platforms']) && !in_array(phodevi::os_under_test(), $repo_index['tests'][$id]['supported_platforms'])) || empty($repo_index['tests'][$id]['title']))
			{
				// Don't show unsupported tests
				continue;
			}
			if(!empty($repo_index['tests'][$id]['status']) && $repo_index['tests'][$id]['status'] != 'Verified')
			{
				// Don't show unsupported tests
				continue;
			}
			if($repo_index['tests'][$id]['last_updated'] < (time() - (60 * 60 * 24 * 365)))
			{
				// Don't show tests not actively maintained
				continue;
			}
			if($repo_index['tests'][$id]['test_type'] == 'Graphics' && !phodevi::is_display_server_active())
			{
				// Don't show graphics tests if no display active
				continue;

			}

			$tests[$id] = $repo_index['tests'][$id];
		}

		uasort($tests, array('pts_openbenchmarking_client', 'compare_test_json_download_counts'));
		return array_slice($tests, 0, $limit);
	}
	public static function new_and_recently_updated_tests($days_old_limit = 14, $test_limit = 10, $just_new = false)
	{
		$only_show_available_cached_tests = pts_network::internet_support_available() == false;
		$tests = array();
		$q = $just_new ? 'first_added' : 'last_updated';

		$cutoff_time = time() - ($days_old_limit * 86400);
		foreach(pts_openbenchmarking::available_tests(false, false, false, false, $only_show_available_cached_tests) as $identifier)
		{
			$repo = substr($identifier, 0, strpos($identifier, '/'));
			$id = substr($identifier, strlen($repo) + 1);
			$repo_index = pts_openbenchmarking::read_repository_index($repo);
			if($repo_index['tests'][$id][$q] < $cutoff_time)
			{
				// Don't show tests not actively maintained
				continue;
			}
			if((!empty($repo_index['tests'][$id]['supported_platforms']) && !in_array(phodevi::os_under_test(), $repo_index['tests'][$id]['supported_platforms'])) || empty($repo_index['tests'][$id]['title']))
			{
				// Don't show unsupported tests
				continue;
			}
			if(!empty($repo_index['tests'][$id]['status']) && $repo_index['tests'][$id]['status'] != 'Verified')
			{
				// Don't show unsupported tests
				continue;
			}
			if($repo_index['tests'][$id]['test_type'] == 'Graphics' && !phodevi::is_display_server_active())
			{
				// Don't show graphics tests if no display active
				continue;

			}

			$tests[$id] = $repo_index['tests'][$id];

			if(count($tests) == $test_limit)
			{
				break;
			}
		}
		uasort($tests, array('pts_openbenchmarking_client', 'compare_test_last_updated'));

		return $tests;
	}
}

?>
