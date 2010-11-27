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

class pts_test_install_request
{
	public $test_profile;
	private $test_files;

	public function __construct($test)
	{
		if($test instanceOf pts_test_profile)
		{
			$this->test_profile = $test;
		}
		else
		{
			$this->test_profile = new pts_test_profile($test);
		}

		$this->test_files = array();
	}
	public static function read_download_object_list($test_identifier)
	{
		// A way to get just the download object list if needed
		$test_install_request = new pts_test_install_request($test_identifier);
		$test_install_request->generate_download_object_list();

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
	public function generate_download_object_list()
	{
		$download_xml_file = $this->test_profile->get_file_download_spec();

		if($download_xml_file != null)
		{
			$xml_parser = new pts_test_downloads_nye_XmlReader($download_xml_file);
			$package_url = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_URL);
			$package_md5 = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_MD5);
			$package_filename = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILENAME);
			$package_filesize = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILESIZE);
			$package_platform = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_PLATFORMSPECIFIC);
			$package_architecture = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_ARCHSPECIFIC);

			foreach(array_keys($package_url) as $i)
			{
				if(!empty($package_platform[$i]))
				{
					$platforms = pts_strings::comma_explode($package_platform[$i]);

					if(!in_array(OPERATING_SYSTEM, $platforms) && !(IS_BSD && BSD_LINUX_COMPATIBLE && in_array("Linux", $platforms)))
					{
						// This download does not match the operating system
						continue;
					}
				}

				if(!empty($package_architecture[$i]))
				{
					$architectures = pts_strings::comma_explode($package_architecture[$i]);

					if(phodevi::cpu_arch_compatible($architectures) == false)
					{
						// This download does not match the CPU architecture
						continue;
					}
				}

				array_push($this->test_files, new pts_test_file_download($package_url[$i], $package_filename[$i], $package_filesize[$i], $package_md5[$i]));
			}
		}
	}
	public function scan_download_caches($local_download_caches, $remote_download_caches, $remote_files)
	{
		$download_location = $this->test_profile->get_install_dir();
		$main_download_cache = pts_strings::append_trailing_slash(pts_config::read_user_config(P_OPTION_CACHE_DIRECTORY, PTS_DOWNLOAD_CACHE_PATH));

		foreach($this->test_files as &$download_package)
		{
			$package_filename = $download_package->get_filename();
			$package_md5 = $download_package->get_md5();

			if(is_file($download_location . $package_filename))
			{
				// File is already there in the test/destination directory, must have been previously downloaded
				// Could add an MD5 check here to ensure validity, but if it made it here it was already valid unless user modified it

				if($download_package->get_filesize() == 0)
				{
					$download_package->set_filesize(filesize($download_location . $package_filename));
				}

				$download_package->set_download_location("IN_DESTINATION_DIR");
			}
			else if(is_file($main_download_cache . $package_filename))
			{
				// In main download cache
				if($download_package->get_filesize() == 0)
				{
					$download_package->set_filesize(filesize($main_download_cache . $package_filename));
				}

				$download_package->set_download_location("MAIN_DOWNLOAD_CACHE", array($main_download_cache . $package_filename));
			}
			else
			{
				// Scan the local download caches
				foreach($local_download_caches as &$cache_directory)
				{
					if(pts_test_installer::validate_md5_download_file($cache_directory . $package_filename, $package_md5))
					{
						if($download_package->get_filesize() == 0)
						{
							$download_package->set_filesize(filesize($cache_directory . $package_filename));
						}

						$download_package->set_download_location("LOCAL_DOWNLOAD_CACHE", array($cache_directory . $package_filename));
						break;
					}
				}

				// If still not found, check remote download caches
				if($download_package->get_download_location_type() == null)
				{
					if(!empty($package_md5) && isset($remote_files[$package_md5]))
					{
						$download_package->set_download_location("REMOTE_DOWNLOAD_CACHE", $remote_files[$package_md5]);
					}
					else
					{
						// Check for files manually
						foreach($remote_download_caches as $remote_dir)
						{
							$remote_file = $remote_dir . $package_filename;
							$stream_context = pts_network::stream_context_create();
							$file_pointer = @fopen($remote_file, 'r', false, $stream_context);

							if($file_pointer !== false)
							{
								$download_package->set_download_location("REMOTE_DOWNLOAD_CACHE", $remote_file);
								break;
							}
						}
					}
				}
			}
		}

	}
}

?>
