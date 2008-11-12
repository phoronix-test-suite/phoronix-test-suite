<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel

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

class batch_setup
{
	public static function run()
	{
		echo "\nThese are the default configuration options for when running the Phoronix Test Suite in a batch mode (i.e. running phoronix-test-suite batch-benchmark universe). Running in a batch mode is designed to be as autonomous as possible, except for where you'd like any end-user interaction.\n\n";
		$batch_options = array();
		$batch_options[0] = pts_bool_question("Save test results when in batch mode (Y/n)?", true);

		if($batch_options[0] == true)
		{
			$batch_options[1] = pts_bool_question("Open the web browser automatically when in batch mode (y/N)?", false);
			$batch_options[2] = pts_bool_question("Auto upload the results to Phoronix Global (Y/n)?", true);
			$batch_options[3] = pts_bool_question("Prompt for test identifier (Y/n)?", true);
			$batch_options[4] = pts_bool_question("Prompt for test description (Y/n)?", true);
			$batch_options[5] = pts_bool_question("Prompt for saved results file-name (Y/n)?", true);
		}
		else
		{
			$batch_options[1] = false;
			$batch_options[2] = false;
			$batch_options[3] = false;
			$batch_options[4] = false;
			$batch_options[5] = false;
		}

		pts_user_config_init(null, null, $batch_options);
		echo "\nBatch settings saved.\n\n";
	}
}

?>
