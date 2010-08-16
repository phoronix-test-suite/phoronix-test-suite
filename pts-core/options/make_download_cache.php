<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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

class make_download_cache implements pts_option_interface
{
	public static function run($r)
	{
		// Generates a PTS Download Cache
		$dc_write_directory = null;
		pts_loader::load_definitions("test-profile-downloads.xml");
		pts_loader::load_definitions("download-cache.xml");

		foreach(pts_test_install_manager::download_cache_locations() as $dc_directory)
		{
			if(is_writable($dc_directory))
			{
				$dc_write_directory = $dc_directory;
				pts_file_io::mkdir($dc_write_directory);
				break;
			}
		}

		if($dc_write_directory == null)
		{
			echo "No writable download cache directory was found. A download cache cannot be created.\n\n";
			return false;
		}

		echo "\nDownload Cache Directory: " . $dc_write_directory . "\n";

		$xml_writer = new tandem_XmlWriter();
		$file_counter = 0;
	
		foreach(pts_tests::installed_tests() as $test)
		{
			$downloads_file = pts_tests::test_resources_location($test) . "downloads.xml";

			if(!is_file($downloads_file))
			{
				continue;
			}

			$xml_parser = new tandem_XmlReader($downloads_file);
			$package_url = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_URL);
			$package_md5 = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_MD5);
			$package_filename = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILENAME);
			$cached = false;

			echo "\nChecking Downloads For: " . $test . "\n";
			$test_install_message = true;
			for($i = 0; $i < count($package_url); $i++)
			{
				if(empty($package_filename[$i]))
				{
					$package_filename[$i] = basename($package_url[$i]);
				}

				if(is_file($dc_write_directory . $package_filename[$i]) && (empty($package_md5[$i]) || md5_file($dc_write_directory . $package_filename[$i]) == $package_md5[$i]))
				{
					echo "\tPreviously Cached: " . $package_filename[$i] . "\n";
					$cached = true;
				}
				else
				{
					if(is_dir(TEST_ENV_DIR . $test . "/"))
					{
						if(is_file(TEST_ENV_DIR . $test . "/" . $package_filename[$i]))
						{
							if(empty($package_md5[$i]) || md5_file(TEST_ENV_DIR . $test . "/" . $package_filename[$i]) == $package_md5[$i])
							{
								echo "\tCaching: " . $package_filename[$i] . "\n";

								if(copy(TEST_ENV_DIR . $test . "/" . $package_filename[$i], $dc_write_directory . $package_filename[$i]))
								{
									$cached = true;
								}
							}
						}
					}
					else
					{
						if($test_install_message)
						{
							echo "\tTest Not Installed\n";
							$test_install_message = false;
						}
					}
				}

				if($cached)
				{
					$xml_writer->addXmlObject(P_CACHE_PACKAGE_FILENAME, $file_counter, $package_filename[$i]);
					$xml_writer->addXmlObject(P_CACHE_PACKAGE_MD5, $file_counter, $package_md5[$i]);
					$file_counter++;
				}
			}
		}

		$cache_xml = $xml_writer->getXML();
		file_put_contents($dc_write_directory . "pts-download-cache.xml", $cache_xml);
		echo "\n";
	}
}

?>
