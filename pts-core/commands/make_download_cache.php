<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2018, Phoronix Media
	Copyright (C) 2008 - 2018, Michael Larabel

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
	const doc_description = 'This option will create a download cache for use by the Phoronix Test Suite. The download cache is created of test files already downloaded to the local system. If passing any test/suite names to make-download-cache, the needed files for those test profiles will first be automatically downloaded before creating the cache.';

	public static function run($r)
	{
		// Force refresh of OB repository to ensure the latest profiles
		pts_openbenchmarking::refresh_repository_lists(null, true);

		// Determine cache location
		$dc_write_directory = pts_client::download_cache_path();
		if(($dc_override = getenv('PTS_DOWNLOAD_CACHE_OVERRIDE')) != false && is_dir($dc_override))
		{
			$dc_write_directory = $dc_override;
		}
		pts_file_io::mkdir($dc_write_directory);
		echo PHP_EOL . 'Download Cache Directory: ' . $dc_write_directory . PHP_EOL;

		if($dc_write_directory == null || !is_writable($dc_write_directory))
		{
			echo 'No writable download cache directory was found. A download cache cannot be created.' . PHP_EOL . PHP_EOL;
			return false;
		}

		if(!empty($r))
		{
			$test_profiles = pts_types::identifiers_to_test_profile_objects($r, true, true);

			if(count($test_profiles) > 0)
			{
				echo PHP_EOL . 'Downloading Test Files For: ' . implode(' ', $test_profiles);
				pts_test_installer::only_download_test_files($test_profiles, $dc_write_directory, getenv('PTS_DOWNLOAD_CACHING_PLATFORM_LIMIT') !== false);
			}
		}

		$json_download_cache = array('phoronix-test-suite' => array(
			'main' => array('generated' => time()),
			'download-cache' => array()
			));

		foreach(pts_tests::partially_installed_tests() as $test)
		{
			$test_profile = new pts_test_profile($test);
			echo PHP_EOL . pts_client::cli_just_bold('Checking Downloads For: ') . $test . PHP_EOL;

			if(getenv('PTS_DOWNLOAD_CACHING_PLATFORM_LIMIT') !== false)
			{
				// Don't get all download files but just those for the given platform
				$tr = new pts_test_install_request($test_profile);
				$tr->generate_download_object_list(true);
				$downloads = $tr->get_download_objects();
			}
			else
			{
				$downloads = $test_profile->get_downloads();
			}

			foreach($downloads as $file)
			{
				$cached_valid = false;
				if(is_file($dc_write_directory . $file->get_filename()) && $file->check_file_hash($dc_write_directory . $file->get_filename()))
				{
					echo pts_client::cli_just_bold('   Previously Cached: ') . $file->get_filename() . PHP_EOL;
					$cached_valid = true;
				}
				else if(is_dir($test_profile->get_install_dir()))
				{
					if(is_file($test_profile->get_install_dir() . $file->get_filename()) && $file->check_file_hash($test_profile->get_install_dir() . $file->get_filename()))
					{
						echo pts_client::cli_just_bold('   Caching: ') . $file->get_filename() . PHP_EOL;
						if(copy($test_profile->get_install_dir() . $file->get_filename(), $dc_write_directory . $file->get_filename()))
						{
							$cached_valid = true;
						}
					}
				}

				if($cached_valid)
				{
					if(!isset($json_download_cache['phoronix-test-suite']['download-cache'][$file->get_filename()]))
					{
						$json_download_cache['phoronix-test-suite']['download-cache'][$file->get_filename()] = array(
							'file_name' => $file->get_filename(),
							'file_size' => $file->get_filesize(),
							'associated_tests' => array($test_profile->get_identifier()),
							'md5' => $file->get_md5(),
							'sha256' => $file->get_sha256(),
							);
					}
					else if($file->get_md5() == $json_download_cache['phoronix-test-suite']['download-cache'][$file->get_filename()]['md5'] && $file->get_sha256() == $json_download_cache['phoronix-test-suite']['download-cache'][$file->get_filename()]['sha256'])
					{
						$json_download_cache['phoronix-test-suite']['download-cache'][$file->get_filename()]['associated_tests'][] = $test_profile->get_identifier();
					}
				}
			}
		}

		// Find files in download-cache/ that weren't part of an installed test (but could have just been tossed in there) to cache
		foreach(glob($dc_write_directory . '/*') as $cached_file)
		{
			$file_name = basename($cached_file);
			if(!isset($json_download_cache['phoronix-test-suite']['download-cache'][$file_name]) && $file_name != 'pts-download-cache.json')
			{
				$json_download_cache['phoronix-test-suite']['download-cache'][$file_name] = array(
					'file_name' => $file_name,
					'file_size' => filesize($cached_file),
					'associated_tests' => array(),
					'md5' => md5_file($cached_file),
					'sha256' => hash_file('sha256', $cached_file),
					);
			}
		}

		$cached_tests = array();
		foreach(pts_openbenchmarking::available_tests(true, true) as $test)
		{
			if(pts_test_install_request::test_files_in_install_dir($test) == false)
			{
				continue;
			}
			$cached_tests[] = $test;
		}
		$json_download_cache['phoronix-test-suite']['cached-tests'] = $cached_tests;

		file_put_contents($dc_write_directory . 'pts-download-cache.json', json_encode($json_download_cache, (defined('JSON_PRETTY_PRINT') ? JSON_PRETTY_PRINT : 0)));
		echo PHP_EOL;
	}
}

?>
