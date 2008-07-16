<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2004-2008, Michael Larabel
	dummy_module.php: A simple "dummy" module to demonstrate the PTS functions

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
	const module_name = "Dummy Module";
	const module_version = "1.0.0";
	const module_description = "This is a simple module intended for developers to just demonstrate some of the module functions.";
	const module_author = "Phoronix Media";

	public static function module_info()
	{
		return "This is a simple module intended for developers to just demonstrate some of the module functions.";
	}

	//
	// General Functions
	//

	public static function __startup()
	{
		echo "\nThe Phoronix Test Suite is starting up!\nCalled: __startup()\n";
	}
	public static function __shutdown()
	{
		echo "\nThe Phoronix Test Suite is done running.\nCalled: __shutdown()\n";
	}

	//
	// Installation Functions
	//

	public static function __pre_install_process()
	{
		echo "\nGetting ready to check for test(s) that need installing...\nCalled: __pre_install_process()\n";
	}
	public static function __pre_test_install()
	{
		echo "\nGetting ready to actually install a test!\nCalled: __pre_test_install()\n";
	}
	public static function __post_test_install()
	{
		echo "\nJust finished installing a test, is there anything to do?\nCalled: __post_test_install()\n";
	}
	public static function __post_install_process()
	{
		echo "\nWe're all done installing any needed tests. Anything to process?\nCalled: __post_install_process()\n";
	}

	//
	// Run Functions
	//

	public static function __pre_run_process()
	{
		echo "\nWe're about to start the actual testing process.\nCalled: __pre_run_process()\n";
	}
	public static function __pre_test_run()
	{
		echo "\nWe're about to run a test! Any pre-run processing?\nCalled: __pre_test_run()\n";
	}
	public static function __interim_test_run()
	{
		echo "\nThis test is being run multiple times for accuracy. Anything to do between tests?\nCalled: __interim_test_run()\n";
	}
	public static function __post_test_run()
	{
		echo "\nWe're all done running this specific test.\nCalled: __post_test_run()\n";
	}
	public static function __post_run_process()
	{
		echo "\nWe're all done with the testing for now.\nCalled: __post_run_process()\n";
	}
}

?>
