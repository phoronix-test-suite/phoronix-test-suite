<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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
	public static function stats_hardware_list()
	{
		return array(
			"cpu" => array("cpu", "model"),
			"cpu_count" => array("cpu", "core-count"),
			"cpu_speed" => array("cpu", "mhz-default-frequency"),
			"chipset" => array("chipset"),
			"motherboard" => array("motherboard"),
			"gpu" => array("gpu", "model")
			);
	}
	public static function stats_software_list()
	{
		return array(
			"os" => array("system", "operating-system"),
			"os_architecture" => array("system", "kernel-architecture"),
			"display_server" => array("system", "display-server"),
			"display_driver" => array("system", "display-driver-string"),
			"desktop" => array("system", "desktop-environment"),
			"compiler" => array("system", "compiler"),
			"file_system" => array("system", "filesystem"),
			"screen_resolution" => array("gpu", "screen-resolution-string")
			);
	}
	public static function request_gsid()
	{
		$upload_data = array(
			"client_version" => PTS_VERSION,
			"client_os" => phodevi::read_property("system", "vendor-identifier")
			);
		$gsid = pts_network::http_upload_via_post(pts_openbenchmarking::openbenchmarking_host() . "extern/request-gsid.php", $upload_data);

		return self::is_valid_gsid_format($gsid) ? $gsid : false;
	}
	public static function is_valid_gsid_format($gsid)
	{
		$gsid_valid = false;

		if(strlen($gsid) == 9)
		{
			if(strlen(pts_strings::keep_in_string(substr($gsid, 0, 6), pts_strings::CHAR_LETTER)) == 6 &&
			strlen(pts_strings::keep_in_string(substr($gsid, 6, 3), pts_strings::CHAR_NUMERIC)) == 3)
			{
				$gsid_valid = true;
			}
		}

		return $gsid_valid;
	}
	public static function linked_repositories()
	{
		return array("pts");
	}
	public static function openbenchmarking_host()
	{
		static $host = null;

		if($host == null)
		{
			// Use HTTPS if OpenSSL is available as a check to see if HTTPS can be handled
			$host = (extension_loaded("openssl") ? "https://" : "http://") . "www.openbenchmarking.org/";
		}

		return $host;
	}
	public static function make_openbenchmarking_request($request, $post = array())
	{
		$url = self::openbenchmarking_host() . "f/client.php";
		$to_post = array_merge(array(
			"r" => $request,
			"client_version" => PTS_CORE_VERSION,
			"gsid" => PTS_GSID,
			"user" => null
			), $post);

		return pts_network::http_upload_via_post($url, $to_post);
	}
	public static function download_test_profile($qualified_identifier, $hash_check = null)
	{
		if(is_file(PTS_TEST_PROFILE_PATH . $qualified_identifier . '/test-definition.xml'))
		{
			return true;
		}

		$file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $qualified_identifier . ".zip";

		if(!is_file($file))
		{
			$test_profile = self::make_openbenchmarking_request('download_test', array('i' => $qualified_identifier));

			if($hash_check == null || $hash_check = sha1($test_profile))
			{
				// save it
				file_put_contents($file, $test_profile);
				$hash_check = null;
			}
		}

		if(!is_file(PTS_TEST_PROFILE_PATH . $qualified_identifier . '/test-definition.xml') && ($hash_check == null || sha1_file($file) == $hash_check))
		{
			// extract it
			pts_file_io::mkdir(PTS_TEST_PROFILE_PATH . dirname($qualified_identifier));
			pts_file_io::mkdir(PTS_TEST_PROFILE_PATH . $qualified_identifier);
			return pts_compression::zip_archive_extract($file, PTS_TEST_PROFILE_PATH . $qualified_identifier);
		}

		return false;
	}
	public static function download_test_suite($qualified_identifier, $hash_check = null)
	{
		if(is_file(PTS_TEST_SUITE_PATH . $qualified_identifier . '/suite-definition.xml'))
		{
			return true;
		}

		$file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $qualified_identifier . ".zip";

		if(!is_file($file))
		{
			$test_profile = self::make_openbenchmarking_request('download_suite', array('i' => $qualified_identifier));

			if($hash_check == null || $hash_check = sha1($test_profile))
			{
				// save it
				file_put_contents($file, $test_profile);
				$hash_check = null;
			}
		}

		if(!is_file(PTS_TEST_SUITE_PATH . $qualified_identifier . '/suite-definition.xml') && ($hash_check == null || sha1_file($file) == $hash_check))
		{
			// extract it
			pts_file_io::mkdir(PTS_TEST_SUITE_PATH . dirname($qualified_identifier));
			pts_file_io::mkdir(PTS_TEST_SUITE_PATH . $qualified_identifier);
			return pts_compression::zip_archive_extract($file, PTS_TEST_SUITE_PATH . $qualified_identifier);
		}

		return false;
	}
	public static function evaluate_string_to_qualifier($supplied, $bind_version = true)
	{
		$qualified = false;
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
				$repos = array($c_repo);
				self::refresh_repository_lists($repos);
			}
		}
		else
		{
			// If it's in the linked repo list it should have refreshed when starting client
			$test = $supplied;
		}

		if(($c = strrpos($test, '-')) !== false)
		{
			$version = substr($test, ($c + 1));
			$version_length = strlen($version);

			// TODO: functionalize this and read against types.xsd
			if($version_length >= 5 && $version_length <= 8 && strlen(pts_strings::keep_in_string($version, (pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DECIMAL))) == $version_length)
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
			$repo_index = self::read_repository_index($repo);

			if(is_array($repo_index) && isset($repo_index['tests'][$test]))
			{
				// The test profile at least exists

				// Looking for a particular test profile version?
				if($version != null)
				{
					if(in_array($version, $repo_index['tests'][$test]['versions']))
					{
						self::download_test_profile("$repo/$test-$version", $repo_index['tests'][$test]['package_hash']);
						return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
					}
				}
				else
				{
					// Assume to use the latest version
					$version = array_shift($repo_index['tests'][$test]['versions']);
					self::download_test_profile("$repo/$test-$version", $repo_index['tests'][$test]['package_hash']);
					return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
				}
			}
			if(is_array($repo_index) && isset($repo_index['suites'][$test]))
			{
				// The test profile at least exists

				// Looking for a particular test profile version?
				if($version != null)
				{
					if(in_array($version, $repo_index['suites'][$test]['versions']))
					{
						self::download_test_suite("$repo/$test-$version", $repo_index['suites'][$test]['package_hash']);
						return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
					}
				}
				else
				{
					// Assume to use the latest version
					$version = array_shift($repo_index['suites'][$test]['versions']);
					self::download_test_suite("$repo/$test-$version", $repo_index['suites'][$test]['package_hash']);
					return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
				}
			}
		}

		return false;
	}
	public static function read_repository_index($repo_name)
	{
		$index_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . ".index";

		if(is_file($index_file))
		{
			$index_file = file_get_contents($index_file);
			$index_file = json_decode($index_file, true);
		}

		return $index_file;
	}
	public static function refresh_repository_lists($repos = null)
	{
		if($repos == null)
		{
			if(define("HAS_REFRESHED_OBO_LIST", true) == false)
			{
				return true;
			}

			$repos = self::linked_repositories();
		}

		foreach($repos as $repo_name)
		{
			$index_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . ".index";

			if(is_file($index_file))
			{
				$repo_index = json_decode(file_get_contents($index_file), true);
				$generated_time = $repo_index['main']['generated'];

				// TODO: time zone differences causes this not to be exact if not on server time
				// Refreshing the indexes once a day should be suffice
				if($generated_time > (time() - 86400))
				{
					// The index is new enough
					continue;
				}
			}

			$server_index = self::make_openbenchmarking_request('repo_index', array('repo' => $repo_name));

			if(json_decode($server_index) != false)
			{
				pts_file_io::mkdir(PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name);
				file_put_contents($index_file, $server_index);
			}
		}
	}
	public static function result_upload_supported(&$result_file)
	{
		foreach($result_file->get_result_objects() as $result_object)
		{
			$test_profile = new pts_test_profile($result_object->test_profile->get_identifier());

			if($test_profile->allow_results_sharing() == false)
			{
				echo "\n" . $result_object->test_profile->get_identifier() . " does not allow test results to be uploaded.\n\n";
				return false;
			}
		}

		return true;
	}
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
		if($result_file->xml_parser->validate() == false)
		{
			echo "\nErrors occurred parsing the result file XML.\n";
			return false;
		}

		// Ensure the results can be shared
		if(self::result_upload_supported($result_file) == false)
		{
			return false;
		}

		/*
		$GlobalUser = pts_global::account_user_name();
		$GlobalKey = pts_config::read_user_config(P_OPTION_GLOBAL_UPLOADKEY, null);
		$return_stream = "";

		$upload_data = array("result_xml" => $ToUpload, "global_user" => $GlobalUser, "global_key" => $GlobalKey, "tags" => $tags, "gsid" => PTS_GSID);
		*/

		// TODO: support for uploading test result logs here, etc
		$composite_xml = $result_file->xml_parser->getXML();
		$to_post = array(
			'composite_xml' => base64_encode($composite_xml),
			'composite_xml_hash' => sha1($composite_xml),
			'local_file_name' => $local_file_name,
			'this_results_identifier' => $results_identifier
			);

		$json_response = self::make_openbenchmarking_request('upload_test_result', $to_post);
		$json_response = json_decode($json_response, true);

		if(isset($json_response['openbenchmarking']['upload']['error']))
		{
			echo "\nERROR:" . $json_response . "\n";
		}
		if(isset($json_response['openbenchmarking']['upload']['url']))
		{
			echo "\nResults Uploaded To: " . $json_response['openbenchmarking']['upload']['url'] . "\n";
			pts_module_manager::module_process("__event_openbenchmarking_upload", $json_response);
		}
		//$json['openbenchmarking']['upload']['id']

		return isset($json_response['openbenchmarking']['upload']['url']) ? $json_response['openbenchmarking']['upload']['url'] : false;
	}
}

?>
