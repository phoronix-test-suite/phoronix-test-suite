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

		$dc_write_directory = pts_strings::add_trailing_slash(pts_client::parse_home_directory(pts_config::read_user_config('PhoronixTestSuite/Options/Installation/CacheDirectory', PTS_DOWNLOAD_CACHE_PATH)));

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
			$cached = false;
			echo PHP_EOL . 'Checking Downloads For: ' . $test . PHP_EOL;

			foreach(pts_test_install_request::read_download_object_list($test_profile, false) as $file)
			{
				if(is_file($dc_write_directory . $file->get_filename()) && $file->check_file_hash($dc_write_directory . $file->get_filename()))
				{
					echo '   Previously Cached: ' . $file->get_filename() . PHP_EOL;
					$cached = true;
				}
				else
				{
					if(is_dir($test_profile->get_install_dir()))
					{
						if(is_file($test_profile->get_install_dir() . $file->get_filename()) && $file->check_file_hash($test_profile->get_install_dir() . $file->get_filename()))
						{
							echo '   Caching: ' . $file->get_filename() . PHP_EOL;

							if(copy($test_profile->get_install_dir() . $file->get_filename(), $dc_write_directory . $file->get_filename()))
							{
								$cached = true;
							}
						}
					}
				}

				if($cached)
				{
					$xml_writer->addXmlNode('PhoronixTestSuite/DownloadCache/Package/FileName', $file->get_filename());
					$xml_writer->addXmlNode('PhoronixTestSuite/DownloadCache/Package/MD5', $file->get_md5());
					$xml_writer->addXmlNode('PhoronixTestSuite/DownloadCache/Package/SHA256', $file->get_sha256());
				}
			}
		}

		$cache_xml = $xml_writer->getXML();
		file_put_contents($dc_write_directory . 'pts-download-cache.xml', $cache_xml);
		echo PHP_EOL;
	}
}

?>
