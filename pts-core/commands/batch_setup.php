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

class batch_setup implements pts_option_interface
{
	const doc_section = 'Batch Testing';
	const doc_description = 'This option is used to configure the batch mode options for the Phoronix Test Suite, which is subsequently written to the user configuration file. Among the options are whether to automatically upload the test results to OpenBenchmarking.org and prompting for the saved file name.';

	public static function run($r)
	{
		echo PHP_EOL . 'These are the default configuration options for when running the Phoronix Test Suite in a batch mode (i.e. running phoronix-test-suite batch-benchmark universe). Running in a batch mode is designed to be as autonomous as possible, except for where you\'d like any end-user interaction.' . PHP_EOL . PHP_EOL;
		$batch_options = array();
		$batch_options['PhoronixTestSuite/Options/BatchMode/SaveResults'] = pts_config::bool_to_string(pts_user_io::prompt_bool_input('Save test results when in batch mode', true));

		if($batch_options['PhoronixTestSuite/Options/BatchMode/SaveResults'] == 'TRUE')
		{
			$batch_options['PhoronixTestSuite/Options/BatchMode/OpenBrowser'] = pts_config::bool_to_string(pts_user_io::prompt_bool_input('Open the web browser automatically when in batch mode', false));
			$batch_options['PhoronixTestSuite/Options/BatchMode/UploadResults'] = pts_config::bool_to_string(pts_user_io::prompt_bool_input('Auto upload the results to OpenBenchmarking.org', true));
			$batch_options['PhoronixTestSuite/Options/BatchMode/PromptForTestIdentifier'] = pts_config::bool_to_string(pts_user_io::prompt_bool_input('Prompt for test identifier', true));
			$batch_options['PhoronixTestSuite/Options/BatchMode/PromptForTestDescription'] = pts_config::bool_to_string(pts_user_io::prompt_bool_input('Prompt for test description', true));
			$batch_options['PhoronixTestSuite/Options/BatchMode/PromptSaveName'] = pts_config::bool_to_string(pts_user_io::prompt_bool_input('Prompt for saved results file-name', true));
		}
		else
		{
			$batch_options['PhoronixTestSuite/Options/BatchMode/OpenBrowser'] = 'FALSE';
			$batch_options['PhoronixTestSuite/Options/BatchMode/UploadResults'] = 'FALSE';
			$batch_options['PhoronixTestSuite/Options/BatchMode/PromptForTestIdentifier'] = 'FALSE';
			$batch_options['PhoronixTestSuite/Options/BatchMode/PromptForTestDescription'] = 'FALSE';
			$batch_options['PhoronixTestSuite/Options/BatchMode/PromptSaveName'] = 'FALSE';
		}

		$batch_options['PhoronixTestSuite/Options/BatchMode/RunAllTestCombinations'] = pts_config::bool_to_string(pts_user_io::prompt_bool_input('Run all test options', true));
		$batch_options['PhoronixTestSuite/Options/BatchMode/Configured'] = 'TRUE';

		pts_config::user_config_generate($batch_options);
		echo PHP_EOL . 'Batch settings saved.' . PHP_EOL . PHP_EOL;
	}
}

?>
