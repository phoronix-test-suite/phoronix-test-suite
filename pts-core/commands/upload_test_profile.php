<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2015, Phoronix Media
	Copyright (C) 2010 - 2015, Michael Larabel

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

class upload_test_profile implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option can be used for uploading a test profile to your account on OpenBenchmarking.org. By uploading your test profile to OpenBenchmarking.org, others are then able to browse and access this test suite for easy distribution in a seamless manner by other Phoronix Test Suite clients.';

	public static function run($r)
	{
		if(pts_openbenchmarking_client::user_name() == false)
		{
			echo PHP_EOL . 'You must first be logged into an OpenBenchmarking.org account.' . PHP_EOL;
			echo PHP_EOL . 'Create An Account: https://openbenchmarking.org/';
			echo PHP_EOL . 'Log-In Command: phoronix-test-suite openbenchmarking-setup' . PHP_EOL . PHP_EOL;
			return false;
		}

		foreach(pts_types::identifiers_to_test_profile_objects($r, false, true) as $test_profile)
		{
			// validate_test_profile
			if(pts_validation::validate_test_profile($test_profile))
			{
				pts_client::$display->generic_heading($test_profile);
				$zip_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $test_profile->get_identifier_base_name() . '-' . $test_profile->get_test_profile_version() . '.zip';
				$zip_created = pts_compression::zip_archive_create($zip_file, pts_file_io::glob($test_profile->get_resource_dir() . '*'));
				if($zip_created == false)
				{
					echo PHP_EOL . 'Failed to create zip file.' . PHP_EOL;
					return false;
				}

				if(filesize($zip_file) > 104857)
				{
					echo PHP_EOL . 'The test profile package is too big.' . PHP_EOL;
					return false;
				}

				$commit_description = pts_user_io::prompt_user_input('Enter a test commit description', false);

				echo PHP_EOL;
				$server_response = pts_openbenchmarking::make_openbenchmarking_request('upload_test_profile', array(
					'tp_identifier' => $test_profile->get_identifier_base_name(),
					'tp_sha1' => sha1_file($zip_file),
					'tp_zip' => base64_encode(file_get_contents($zip_file)),
					'tp_zip_name' => basename($zip_file),
					'commit_description' => $commit_description
					));
				echo PHP_EOL;

				$json = json_decode($server_response, true);
				if(isset($json['openbenchmarking']['upload']['error']) && !empty($json['openbenchmarking']['upload']['error']))
				{
					echo 'ERROR: ' . $json['openbenchmarking']['upload']['error'] . PHP_EOL;
				}
				if(isset($json['openbenchmarking']['upload']['id']) && !empty($json['openbenchmarking']['upload']['id']))
				{
					echo 'Command: phoronix-test-suite benchmark ' . $json['openbenchmarking']['upload']['id'] . PHP_EOL;
				}
				if(isset($json['openbenchmarking']['upload']['url']) && !empty($json['openbenchmarking']['upload']['url']))
				{
					pts_openbenchmarking::refresh_repository_lists(null, true);
					echo 'URL: ' . $json['openbenchmarking']['upload']['url'] . PHP_EOL;
				}
				echo PHP_EOL;

				// TODO: chmod +x the .sh files, appropriate permissions elsewhere
				unlink($zip_file);
			}
		}
	}
}

?>
