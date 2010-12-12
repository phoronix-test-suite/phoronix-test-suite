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

class pts_test_install_manager
{
	private $tests_to_install;

	public function __construct()
	{
		$this->tests_to_install = array();
	}
	public function add_test_profile($test_profile)
	{
		$added = false;

		if(($e = pts_client::read_env("SKIP_TESTS")) != false && in_array($test_profile->get_identifier(false), pts_strings::comma_explode($e)))
		{
			//pts_client::$display->test_install_error($test_profile->get_identifier() . " is being skipped from installation.");
		}
		else
		{
			$added = pts_arrays::unique_push($this->tests_to_install, new pts_test_install_request($test_profile));
		}

		return $added;
	}
	public function generate_download_file_lists()
	{
		pts_client::$display->test_install_progress_start("Determining File Requirements");
		$test_count = count($this->tests_to_install);

		foreach($this->tests_to_install as $i => &$test_install_request)
		{
			$test_install_request->generate_download_object_list();
			pts_client::$display->test_install_progress_update(($i / $test_count));
		}
		pts_client::$display->test_install_progress_completed();
	}
	public function check_download_caches_for_files()
	{
		pts_client::$display->test_install_progress_start("Searching Download Caches");
		$test_count = count($this->tests_to_install);

		$remote_files = self::remote_files_available_in_download_caches();
		$local_download_caches = self::local_download_caches();
		$remote_download_caches = self::remote_download_caches();

		foreach($this->tests_to_install as $i => &$test_install_request)
		{
			$test_install_request->scan_download_caches($local_download_caches, $remote_download_caches, $remote_files);
			pts_client::$display->test_install_progress_update(($i / $test_count));
		}
		pts_client::$display->test_install_progress_completed();
	}
	public function remote_files_available_in_download_caches()
	{
		pts_load_xml_definitions("download-cache.xml");
		$remote_download_files = array();

		foreach(self::remote_download_caches() as $dc_directory)
		{
			if(($xml_dc_file = pts_network::http_get_contents($dc_directory . "pts-download-cache.xml")) != false)
			{
				$xml_dc_parser = new nye_XmlReader($xml_dc_file);
				$dc_file = $xml_dc_parser->getXMLArrayValues(P_CACHE_PACKAGE_FILENAME);
				$dc_md5 = $xml_dc_parser->getXMLArrayValues(P_CACHE_PACKAGE_MD5);

				foreach(array_keys($dc_file) as $i)
				{
					if(!isset($remote_download_files[$dc_md5[$i]]))
					{
						$remote_download_files[$dc_md5[$i]] = array();
					}

					array_push($remote_download_files[$dc_md5[$i]], $dc_directory . $dc_file[$i]);
				}
			}
		}

		return $remote_download_files;
	}
	public function remote_download_caches()
	{
		$cache_directories = array();

		foreach(self::download_cache_locations() as $dc_directory)
		{
			if(pts_strings::is_url($dc_directory))
			{
				array_push($cache_directories, $dc_directory);
			}
		}

		return $cache_directories;
	}
	public function local_download_caches()
	{
		$local_cache_directories = array();

		foreach(self::download_cache_locations() as $dc_directory)
		{
			if(!pts_strings::is_url($dc_directory) && is_dir($dc_directory))
			{
				array_push($local_cache_directories, $dc_directory);
			}
		}

		return $local_cache_directories;
	}
	public function get_test_run_requests()
	{
		return $this->tests_to_install;
	}
	public function tests_to_install_count()
	{
		return count($this->tests_to_install);
	}
	public function next_in_install_queue()
	{
		return count($this->tests_to_install) > 0 ? array_shift($this->tests_to_install) : false;
	}
	public static function download_cache_locations()
	{
		static $cache_directories = null;

		if($cache_directories == null)
		{
			$cache_directories = array();

			// Phoronix Test Suite System Cache Directories
			$additional_dir_checks = array("/var/cache/phoronix-test-suite/download-cache/", "/var/cache/phoronix-test-suite/");
			foreach($additional_dir_checks as $dir_check)
			{
				if(is_dir($dir_check))
				{
					array_push($cache_directories, $dir_check);
					break;
				}
			}

			// User Defined Directory Checking
			$dir_string = ($dir = pts_client::read_env("PTS_DOWNLOAD_CACHE")) != false ? $dir : null;

			foreach(pts_strings::colon_explode($dir_string) as $dir_check)
			{
				if($dir_check == null)
				{
					continue;
				}

				$dir_check = pts_client::parse_home_directory($dir_check);

				if(pts_strings::is_url($dir_check) == false && !is_dir($dir_check))
				{
					continue;
				}

				array_push($cache_directories, pts_strings::add_trailing_slash($dir_check));
			}

			if(pts_config::read_bool_config(P_OPTION_CACHE_SEARCHMEDIA, "TRUE"))
			{
				$download_cache_dirs = array_merge(
				pts_file_io::glob("/media/*/download-cache/"),
				pts_file_io::glob("/Volumes/*/download-cache/")
				);

				foreach($download_cache_dirs as $dir)
				{
					array_push($cache_directories, $dir);
				}
			}
		}

		return $cache_directories;
	}
}

?>
