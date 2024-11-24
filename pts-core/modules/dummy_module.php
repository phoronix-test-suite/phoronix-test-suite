<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2016, Phoronix Media
	Copyright (C) 2008 - 2016, Michael Larabel
	dummy_module.php: A simple 'dummy' module to demonstrate the PTS functions

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

class dummy_module extends pts_module_interface
{
	const module_name = 'Dummy Module';
	const module_version = '1.1.0';
	const module_description = 'This is a simple module intended for developers to just demonstrate some of the module functions.';
	const module_author = 'Phoronix Media';

	public static function module_info()
	{
		return 'This is a simple module intended for developers to just demonstrate some of the module functions.';
	}
	public static function user_commands()
	{
		return array('dummy_command' => 'sample_command');
	}

	//
	// User Commands
	//

	public static function sample_command()
	{
		echo PHP_EOL . 'This is a sample function running from a module!' . PHP_EOL;
	}

	//
	// General Functions
	//

	public static function __startup()
	{
		echo PHP_EOL . 'The Phoronix Test Suite is starting up!' . PHP_EOL . 'Called: __startup()' . PHP_EOL;
	}
	public static function __shutdown()
	{
		echo PHP_EOL . 'The Phoronix Test Suite is done running.' . PHP_EOL . 'Called: __shutdown()' . PHP_EOL;
	}

	//
	// Installation Functions
	//

	public static function __pre_install_process()
	{
		echo PHP_EOL . 'Getting ready to check for test(s) that need installing...' . PHP_EOL . 'Called: __pre_install_process()' . PHP_EOL;
	}
	public static function __pre_test_download()
	{
		echo PHP_EOL . 'Getting ready to download files for a test!' . PHP_EOL . 'Called: __pre_test_download()' . PHP_EOL;
	}
	public static function __interim_test_download()
	{
		echo PHP_EOL . 'Just finished downloading a file for a test.' . PHP_EOL . 'Called: __interim_test_download()' . PHP_EOL;
	}
	public static function __post_test_download()
	{
		echo PHP_EOL . 'Just finished the download process for a test.' . PHP_EOL . 'Called: __post_test_download()' . PHP_EOL;
	}
	public static function __pre_test_install()
	{
		echo PHP_EOL . 'Getting ready to actually install a test!' . PHP_EOL . 'Called: __pre_test_install()' . PHP_EOL;
	}
	public static function __post_test_install()
	{
		echo PHP_EOL . 'Just finished installing a test, is there anything to do?' . PHP_EOL . 'Called: __post_test_install()' . PHP_EOL;
	}
	public static function __post_install_process()
	{
		echo PHP_EOL . 'We\'re all done installing any needed tests. Anything to process?' . PHP_EOL . 'Called: __post_install_process()' . PHP_EOL;
	}

	//
	// Run Functions
	//

	public static function __pre_run_process()
	{
		echo PHP_EOL . 'We\'re about to start the actual testing process.' . PHP_EOL . 'Called: __pre_run_process()' . PHP_EOL;
	}
	public static function __pre_test_run()
	{
		echo PHP_EOL . 'We\'re about to run a test! Any pre-run processing?' . PHP_EOL . 'Called: __pre_test_run()' . PHP_EOL;
	}
	public static function __calling_test_script()
	{
		echo PHP_EOL . 'We\'re about to call the test script! Any pre-execution processing or bits to set?' . PHP_EOL . 'Called: __calling_test_script()' . PHP_EOL;
	}
	public static function __test_running()
	{
		echo PHP_EOL . 'We just started running a test! Want to tap the PID or anything?' . PHP_EOL . 'Called: __test_running()' . PHP_EOL;
	}
	public static function __interim_test_run()
	{
		echo PHP_EOL . 'This test is being run multiple times for accuracy. Anything to do between tests?' . PHP_EOL . 'Called: __interim_test_run()' . PHP_EOL;
	}
	public static function __post_test_run()
	{
		echo PHP_EOL . 'We\'re all done running this specific test.' . PHP_EOL . 'Called: __post_test_run()' . PHP_EOL;
	}
	public static function __post_run_process()
	{
		echo PHP_EOL . 'We\'re all done with the testing for now.' . PHP_EOL . 'Called: __post_run_process()' . PHP_EOL;
	}
	public static function __test_log_output()
	{
		echo PHP_EOL . 'Log file available.' . PHP_EOL . 'Called: __test_log_output()' . PHP_EOL;
	}
	public static function __post_test_run_system_logs()
	{
		echo PHP_EOL . 'System logs available.' . PHP_EOL . 'Called: __post_test_run_system_logs()' . PHP_EOL;
	}
}

?>
