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

class pts_test_install_request
{
	public $test_profile;
	public $test_files;
	public $compiler_mask_dir = false;
	public $install_error = null;
	public $special_environment_vars;
	protected $has_generated_download_object_list = false;

	public function __construct($test)
	{
		if($test instanceof pts_test_profile)
		{
			$this->test_profile = $test;
		}
		else
		{
			$this->test_profile = new pts_test_profile($test);
		}

		$this->test_files = array();
		$this->special_environment_vars = array();
	}
	public static function read_download_object_list($test, $do_file_checks = true)
	{
		// A way to get just the download object list if needed
		$test_install_request = new pts_test_install_request($test);
		$test_install_request->generate_download_object_list($do_file_checks);

		return $test_install_request->get_download_objects();
	}
	public function __toString()
	{
		return $this->test_profile->get_identifier();
	}
	public function get_download_objects()
	{
		return $this->test_files;
	}
	public function get_download_object_count()
	{
		return count($this->test_files);
	}
	public function generate_download_object_list($do_file_checks = true)
	{
		// no need to repeat if called multiple times
		if($this->has_generated_download_object_list)
		{
			return true;
		}
		$this->has_generated_download_object_list = true;

		foreach($this->test_profile->get_downloads() as $download)
		{
			// Check for platform compatibility
			$platforms = $download->get_platform_array();
			if(!empty($platforms) && $do_file_checks)
			{
				if(!in_array(phodevi::os_under_test(), $platforms) && !(phodevi::is_bsd() && in_array('Linux', $platforms) && (pts_client::executable_in_path('kldstat') && strpos(shell_exec('kldstat -n linux 2>&1'), 'linux.ko') != false)))
				{
					// This download does not match the operating system
					continue;
				}
			}

			// Check for architecture compatibility
			$architectures = $download->get_architecture_array();
			if(!empty($architectures) && $do_file_checks)
			{
				if(phodevi::cpu_arch_compatible($architectures) == false)
				{
					// This download does not match the CPU architecture
					continue;
				}
			}
			$this->test_files[] = $download;
		}
	}
	public static function test_files_available_via_cache(&$test_profile, $only_check_local_system = false)
	{
		static $remote_files, $local_download_caches, $remote_download_caches, $phoromatic_server_caches, $cached = false;

		$install_request = new pts_test_install_request($test_profile);

		if($only_check_local_system)
		{
			$remote_files = false;
			$local_download_caches = pts_test_install_manager::local_download_caches();
			$remote_download_caches = false;
			$phoromatic_server_caches = false;
			$cached = false;
		}
		else if($cached == false)
		{
			$remote_files = pts_test_install_manager::remote_files_available_in_download_caches();
			$local_download_caches = pts_test_install_manager::local_download_caches();
			$remote_download_caches = pts_test_install_manager::remote_download_caches();
			$phoromatic_server_caches = pts_test_install_manager::phoromatic_download_server_caches();
			$cached = true;
		}

		$install_request->generate_download_object_list();
		$all_files_accessible = $install_request->scan_download_caches($local_download_caches, $remote_download_caches, $remote_files, $phoromatic_server_caches, true, true);

		if($all_files_accessible == false)
		{
			return false;
		}

		foreach($install_request->test_profile->extended_test_profiles() as $extended_test_profile)
		{
			if(self::test_files_available_via_cache($extended_test_profile, $only_check_local_system) == false)
			{
				return false;
			}
		}

		return true;
	}
	public static function test_files_available_on_local_system(&$test_profile)
	{
		if(!is_file(PTS_TEST_PROFILE_PATH . $test_profile . '/test-definition.xml'))
		{
			return false;
		}

		return self::test_files_available_via_cache($test_profile, true);
	}
	public static function test_files_in_install_dir(&$test_profile)
	{
		$install_request = new pts_test_install_request($test_profile);
		$install_request->generate_download_object_list();
		$download_location = $install_request->test_profile->get_install_dir();

		foreach($install_request->test_files as &$download_package)
		{
			if(!is_file($download_location . $download_package->get_filename()))
			{
				return false;
			}
		}

		return true;
	}
	public function scan_download_caches(&$local_download_caches, &$remote_download_caches, &$remote_files, &$phoromatic_server_caches, $skip_extra_checks = false, $only_checking_for_cached_tests = false)
	{
		$download_location = $this->test_profile->get_install_dir();
		$main_download_cache = pts_client::download_cache_path();

		foreach($this->test_files as &$download_package)
		{
			$package_filename = $download_package->get_filename();

			if(is_file($download_location . $package_filename))
			{
				// File is already there in the test/destination directory, must have been previously downloaded
				// Could add an MD5 check here to ensure validity, but if it made it here it was already valid unless user modified it

				if(!$skip_extra_checks && $download_package->get_filesize() == 0)
				{
					$download_package->set_filesize(filesize($download_location . $package_filename));
				}

				$download_package->set_download_location('IN_DESTINATION_DIR');
			}
			else if(is_file($main_download_cache . $package_filename))
			{
				// In main download cache
				if(!$skip_extra_checks && $download_package->get_filesize() == 0)
				{
					$download_package->set_filesize(filesize($main_download_cache . $package_filename));
				}

				$download_package->set_download_location('MAIN_DOWNLOAD_CACHE', array($main_download_cache . $package_filename));
			}
			else if(is_file(PTS_SHARE_PATH . 'download-cache/' . $package_filename))
			{
				// In system's /usr/share download cache
				if(!$skip_extra_checks && $download_package->get_filesize() == 0)
				{
					$download_package->set_filesize(filesize(PTS_SHARE_PATH . 'download-cache/' . $package_filename));
				}

				$download_package->set_download_location('MAIN_DOWNLOAD_CACHE', array(PTS_SHARE_PATH . 'download-cache/' . $package_filename));
			}
			else
			{
				// Scan the local download caches
				foreach($local_download_caches as &$cache_directory)
				{
					if(is_file($cache_directory . $package_filename) && ($skip_extra_checks || $download_package->check_file_hash($cache_directory . $package_filename)))
					{
						if(!$skip_extra_checks && $download_package->get_filesize() == 0)
						{
							$download_package->set_filesize(filesize($cache_directory . $package_filename));
						}

						$download_package->set_download_location('LOCAL_DOWNLOAD_CACHE', array($cache_directory . $package_filename));
						break;
					}
				}

				// Look-aside download cache copy
				// Check to see if the same package name with the same package check-sum is already present in another test installation
				$lookaside_copy = pts_test_install_manager::file_lookaside_test_installations($download_package);
				if($lookaside_copy)
				{
					if(!$skip_extra_checks && $download_package->get_filesize() == 0)
					{
						$download_package->set_filesize(filesize($lookaside_copy));
					}

					$download_package->set_download_location('LOOKASIDE_DOWNLOAD_CACHE', array($lookaside_copy));
				}

				// Check Phoromatic server caches
				if($download_package->get_download_location_type() == null && $phoromatic_server_caches)
				{
					foreach($phoromatic_server_caches as $server_url => $repo)
					{
						if(isset($repo[$package_filename]) && ($skip_extra_checks || $repo[$package_filename]['md5'] == $download_package->get_md5() || $repo[$package_filename]['sha256'] == $download_package->get_sha256() || ($download_package->get_sha256() == null && $download_package->get_md5() == null)))
						{
							$download_package->set_download_location('REMOTE_DOWNLOAD_CACHE', array($server_url . '/download-cache.php?download=' . $package_filename));
							break;
						}
					}
				}

				// If still not found, check remote download caches
				if($download_package->get_download_location_type() == null)
				{
					if(isset($remote_files[$package_filename]))
					{
						$download_package->set_download_location('REMOTE_DOWNLOAD_CACHE', $remote_files[$package_filename]);
					}
					else if(!empty($remote_download_caches))
					{
						// Check for files manually
						foreach($remote_download_caches as $remote_dir)
						{
							$remote_file = $remote_dir . $package_filename;
							$stream_context = pts_network::stream_context_create();
							$file_pointer = fopen($remote_file, 'r', false, $stream_context);

							if($file_pointer !== false)
							{
								$download_package->set_download_location('REMOTE_DOWNLOAD_CACHE', $remote_file);
								break;
							}
						}
					}
				}
			}

			if($only_checking_for_cached_tests && $download_package->get_download_location_type() == null)
			{
				return false;
			}
		}

		if($only_checking_for_cached_tests)
		{
			return true;
		}
	}
	public function get_arguments_description()
	{
		return null;
	}
}

?>
