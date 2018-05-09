<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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

class create_test_profile implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = 'This option can be used for creating a Phoronix Test Suite test profile by answering questions about the test for constructing the test profile XML meta-data and handling other boiler-plate basics for getting started in developing new tests.';

	public static function run($r)
	{
		echo 'The create-test-profile helper will attempt to walk you through creating the basics of creating a new test profile for execution by the Phoronix Test Suite. Follow the prompts to begin filling out the XML meta-data that defines a test profile.' . PHP_EOL;

		do
		{
			$is_valid = true;
			$input = pts_user_io::prompt_user_input('Enter an identifier/name for the test profile', false, false);
			$input = pts_strings::keep_in_string(str_replace(' ', '-', strtolower($input)), pts_strings::CHAR_LETTER | pts_strings::CHAR_NUMERIC | pts_strings::CHAR_DASH);

			if(pts_test_profile::is_test_profile($input))
			{
				$is_valid = false;
				echo 'There is already a ' . $input . ' test profile.' . PHP_EOL;
			}
			else if(pts_test_suite::is_suite($input))
			{
				$is_valid = false;
				echo 'There is already a test suite named ' . $input . '.' . PHP_EOL;
			}
			else if(pts_result_file::is_test_result_file($input))
			{
				$is_valid = false;
				echo 'There is already a result file named ' . $input . '.' . PHP_EOL;
			}
		}
		while(!$is_valid);

		$tp_identifier = 'local/'. $input;
		$tp_path = PTS_TEST_PROFILE_PATH . $tp_identifier;
		echo 'Creating test profile: ' . $tp_identifier . ' @ ' . $tp_path . PHP_EOL;
		pts_file_io::mkdir($tp_path);

		$types = pts_validation::process_xsd_types();

		pts_client::$display->generic_heading('test-definition.xml Creation');
		$writer = new nye_XmlWriter();
		pts_validation::xsd_to_cli_creator(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile.xsd', $writer, $types);
		$writer->saveXMLFile($tp_path . '/test-definition.xml');
		echo 'Generated: ' . $tp_path . '/test-definition.xml' . PHP_EOL;

		pts_client::$display->generic_heading('downloads.xml Creation');
		$writer = new nye_XmlWriter();
		do
		{
			pts_validation::xsd_to_cli_creator(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/test-profile-downloads.xsd', $writer, $types);
		}
		while(pts_user_io::prompt_bool_input('Add another file/download?', -1));
		$writer->saveXMLFile($tp_path . '/downloads.xml');
		echo 'Generated: ' . $tp_path . '/downloads.xml' . PHP_EOL;

		/*
		pts_client::$display->generic_heading('results-definition.xml Creation');
		$writer = new nye_XmlWriter();
		pts_validation::xsd_to_cli_creator(pts_openbenchmarking::openbenchmarking_standards_path() . 'schemas/results-parser.xsd', $writer, $types);
		$writer->saveXMLFile($tp_path . '/results-definition.xml');
		echo 'Generated: ' . $tp_path . '/results-definition.xml' . PHP_EOL;
		*/

		$test_profile = new pts_test_profile($tp_identifier);
		$result_scale = $test_profile->get_result_scale();
		$test_executable = $test_profile->get_test_executable();

		if(!is_file($tp_path . '/install.sh'))
		{
			$sample_install_sh = '#!/bin/sh' . PHP_EOL . '# Auto-generated install.sh script for starting/helping the test profile creation process...' . PHP_EOL . PHP_EOL;

			$download_extract_helpers = array();
			foreach($test_profile->get_downloads() as $file)
			{
				$file = $file->get_filename();
				switch(substr($file, strrpos($file, '.') + 1))
				{
					case 'zip':
						$download_extract_helpers[] = 'unzip -o ' . $file;
						break;
					case 'gz':
					case 'bz2':
					case 'xz':
					case 'tar':
						$download_extract_helpers[] = 'tar -xvf ' . $file;
						break;
					case 'exe':
					case 'msi':
					case 'run':
						$download_extract_helpers[] = './' . $file;
						break;
				}
			}

			if(!empty($download_extract_helpers))
			{
				$sample_install_sh . '# Presumably you want to extract/run the downloaded files for setting up the test case...' . PHP_EOL;
				$sample_install_sh .= implode(PHP_EOL, $download_extract_helpers) . PHP_EOL;
			}

			$sample_install_sh .= PHP_EOL . 'echo "#!/bin/sh' . PHP_EOL;
			$sample_install_sh .= '# the actual running/execution of the test, etc... This is called at run-time.' . PHP_EOL;
			$sample_install_sh .= '# The program under test and/or any parsing/wrapper scripts should then pipe the results to \$LOG_FILE for parsing.' . PHP_EOL;
			$sample_install_sh .= '# Passed to the script as arguments are any of the test arguments/options as defined by the test-definition.xml.' . PHP_EOL;
			$sample_install_sh .= PHP_EOL . '# Editing the test profile\'s results-definition.xml controls how the Phoronix Test Suite will capture the program\'s result.' . PHP_EOL;
			$sample_install_sh .= '# STATIC EXAMPLE below coordinated with the stock result-definition.xml.' . PHP_EOL;
			$sample_install_sh .= 'echo \"Program Output...\nProgram Output....\nResult: 55.5\" > \$LOG_FILE' . PHP_EOL;
			$sample_install_sh .= 'echo \$? > ~/test-exit-status' . PHP_EOL;
			$sample_install_sh .= PHP_EOL . '" > ~/' . $test_executable . PHP_EOL;
			$sample_install_sh .= 'chmod +x ~/' . $test_executable . PHP_EOL;

			$sample_install_sh .= PHP_EOL . '# Check out the `phoronix-test-suite debug-run` command when trying to debug your install/run behavior' . PHP_EOL;

			file_put_contents($tp_path . '/install.sh', $sample_install_sh);
		}

		if(!is_file($tp_path . '/results-definition.xml'))
		{
			file_put_contents($tp_path . '/results-definition.xml', '<?xml version="1.0"?>
<PhoronixTestSuite>
  <ResultsParser>
    <OutputTemplate>Result: #_RESULT_#</OutputTemplate>
  </ResultsParser>
</PhoronixTestSuite>');
		}

		if(!is_file($tp_path . '/pre.sh'))
		{
			file_put_contents($tp_path . '/pre.sh', '#!/bin/sh
# pre.sh is called prior to running the test, if needed to setup any sample data / create a test file / seed a cache / related pre-run tasks');
		}

		if(!is_file($tp_path . '/interim.sh'))
		{
			file_put_contents($tp_path . '/interim.sh', '#!/bin/sh
# interim.sh is called in between test runs for when a test profile is set via TimesToRun to execute multiple times. This is useful for restoring a program\'s state or any other changes that need to be made in between runs.');
		}

		if(!is_file($tp_path . '/post.sh'))
		{
			file_put_contents($tp_path . '/post.sh', '#!/bin/sh
# post.sh is called after the test has been run, if needed to flush any cache / temporary files, clean-up anything, etc.');
		}

		// extract downloads
		// do basic output for creating benchmark file
		// done?
	}
}

?>
