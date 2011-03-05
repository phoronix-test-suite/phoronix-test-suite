<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel

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
	const doc_section = 'Test Installation';
	const doc_description = 'This option will create a download cache for use by the Phoronix Test Suite.';

	public static function run($r)
	{
		// Generates a PTS Download Cache
		$dc_write_directory = null;
		pts_load_xml_definitions('test-profile-downloads.xml');
		pts_load_xml_definitions('download-cache.xml');

		$dc_write_directory = pts_strings::add_trailing_slash(pts_client::parse_home_directory(pts_config::read_user_config(P_OPTION_CACHE_DIRECTORY, PTS_DOWNLOAD_CACHE_PATH)));

		if($dc_write_directory == null || !is_writable($dc_write_directory))
		{
			echo 'No writable download cache directory was found. A download cache cannot be created.' . PHP_EOL . PHP_EOL;
			return false;
		}

		echo PHP_EOL . 'Download Cache Directory: ' . $dc_write_directory . PHP_EOL;

		$xml_writer = new nye_XmlWriter();

		foreach(pts_tests::installed_tests() as $test)
		{
			$test_profile = new pts_test_profile($test);
			$downloads_file = $test_profile->get_file_download_spec();

			if(!is_file($downloads_file))
			{
				continue;
			}

			$xml_parser = new nye_XmlReader($downloads_file);
			$package_url = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_URL);
			$package_md5 = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_MD5);
			$package_filename = $xml_parser->getXMLArrayValues(P_DOWNLOADS_PACKAGE_FILENAME);
			$cached = false;

			echo PHP_EOL . 'Checking Downloads For: ' . $test . PHP_EOL;
			$test_install_message = true;
			for($i = 0; $i < count($package_url); $i++)
			{
				if(empty($package_filename[$i]))
				{
					$package_filename[$i] = basename($package_url[$i]);
				}

				if(is_file($dc_write_directory . $package_filename[$i]) && (empty($package_md5[$i]) || md5_file($dc_write_directory . $package_filename[$i]) == $package_md5[$i]))
				{
					echo '   Previously Cached: ' . $package_filename[$i] . PHP_EOL;
					$cached = true;
				}
				else
				{
					if(is_dir($test_profile->get_install_dir()))
					{
						if(is_file($test_profile->get_install_dir() . $package_filename[$i]))
						{
							if(empty($package_md5[$i]) || md5_file($test_profile->get_install_dir() . $package_filename[$i]) == $package_md5[$i])
							{
								echo '   Caching: ' . $package_filename[$i] . PHP_EOL;

								if(copy($test_profile->get_install_dir() . $package_filename[$i], $dc_write_directory . $package_filename[$i]))
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
							echo '   Test Not Installed' . PHP_EOL;
							$test_install_message = false;
						}
					}
				}

				if($cached)
				{
					$xml_writer->addXmlNode(P_CACHE_PACKAGE_FILENAME, $package_filename[$i]);
					$xml_writer->addXmlNode(P_CACHE_PACKAGE_MD5, $package_md5[$i]);
				}
			}
		}

		$cache_xml = $xml_writer->getXML();
		file_put_contents($dc_write_directory . 'pts-download-cache.xml', $cache_xml);
		echo PHP_EOL;
	}
}

?>
