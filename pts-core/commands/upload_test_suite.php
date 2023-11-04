<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2016, Phoronix Media
	Copyright (C) 2010 - 2016, Michael Larabel

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

class upload_test_suite implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option can be used for uploading a test suite to your account on OpenBenchmarking.org. By uploading your test suite to OpenBenchmarking.org, others are then able to browse and access this test suite for easy distribution.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_test_suite', 'is_suite'), null)
		);
	}
	public static function run($r)
	{
		if(pts_openbenchmarking_client::user_name() == false)
		{
			echo PHP_EOL . 'You must first be logged into an OpenBenchmarking.org account.' . PHP_EOL;
			echo PHP_EOL . 'Create An Account: https://openbenchmarking.org/';
			echo PHP_EOL . 'Log-In Command: phoronix-test-suite openbenchmarking-setup' . PHP_EOL . PHP_EOL;
			return false;
		}

		if(($test_suite = pts_types::identifier_to_object($r[0])) != false)
		{
			pts_client::$display->generic_heading($r[0]);

			if(pts_validation::validate_test_suite($test_suite) && $test_suite->get_file_location() != null)
			{
				$zip_file = PTS_OPENBENCHMARKING_SCRATCH_PATH . $test_suite->get_identifier(false) . '-' . $test_suite->get_version() . '.zip';
				$zip_created = pts_compression::zip_archive_create($zip_file, $test_suite->get_file_location());

				if($zip_created == false)
				{
					echo PHP_EOL . 'Failed to create zip file.' . PHP_EOL;
					return false;
				}

				$commit_description = pts_user_io::prompt_user_input('Enter a suite commit description', false);

				echo PHP_EOL;
				$server_response = pts_openbenchmarking::make_openbenchmarking_request('upload_test_suite', array(
					'ts_identifier' => $test_suite->get_identifier_base_name(),
					'ts_sha1' => sha1_file($zip_file),
					'ts_zip' => base64_encode(file_get_contents($zip_file)),
					'ts_zip_name' => basename($zip_file),
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

				unlink($zip_file);
			}
		}
	}
}

?>
