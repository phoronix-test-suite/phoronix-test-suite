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
	public static function linked_repositories()
	{
		return array("pts");
	}
	public static function make_openbenchmarking_request($request, $post = array())
	{
		$url = "http://www.openbenchmarking.org/f/client.php";
		$to_post = array_merge(array(
			"r" => $request,
			"user" => null,
			"client_version" => PTS_CORE_VERSION
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
				$test = substr($test, $c);
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
					if(in_array($version, $repo_index['tests'][$test]['profile_versions']))
					{
						self::download_test_profile("$repo/$test-$version", $repo_index['tests'][$test]['package_hash']);
						return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
					}
				}
				else
				{
					// Assume to use the latest version
					$version = array_shift($repo_index['tests'][$test]['profile_versions']);
					self::download_test_profile("$repo/$test-$version", $repo_index['tests'][$test]['package_hash']);
					return $repo . '/' . $test . ($bind_version ? '-' . $version : null);
				}
			}
		}

		return false;
	}
	public static function read_repository_index($repo_name)
	{
		$index_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . ".tests";

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
			$index_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name . ".tests";

			if(is_file($index_file))
			{
				$test_index = json_decode(file_get_contents($index_file), true);
				$generated_time = $test_index['main']['generated'];

				// TODO: time zone differences causes this not to be exact if not on server time
				// Refreshing the indexes once a day should be suffice
				if($generated_time > (time() - 86400))
				{
					// The index is new enough
					continue;
				}
			}

			$server_index = self::make_openbenchmarking_request('test_index', array('repo' => $repo_name));

			if(json_decode($server_index) != false)
			{
				pts_file_io::mkdir(PTS_OPENBENCHMARKING_SCRATCH_PATH . $repo_name);
				file_put_contents($index_file, $server_index);
			}
		}
	}
}

?>
