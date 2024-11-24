<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2019, Phoronix Media
	Copyright (C) 2008 - 2019, Michael Larabel
	pts_module_interface.php: The generic Phoronix Test Suite module object that is extended by the specific modules/plug-ins

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

class pts_module_interface
{
	const module_name = "Generic Module";
	const module_version = "1.0.0";
	const module_description = "A description of the module.";
	const module_author = "Module Creator";

	public static $module_store_vars = array();

	public static function module_info()
	{

	}
	public static function module_setup()
	{
		return array();
	}
	public static function module_setup_validate($module_options_array)
	{
		return $module_options_array;
	}
	public static function module_environment_variables()
	{
		return array();
	}
	public static function user_commands()
	{
		return array();
	}

	//
	// The below methods are optional and can be implemented by any module
	//

	/*

	//
	// General Functions
	//

	public static function __startup($obj = null)
	{
		return;
	}
	public static function __shutdown($obj = null)
	{
		return;
	}

	//
	// Option Functions
	//

	public static function __pre_option_process($obj = null)
	{
		// Passed is a string containing the name of the option command about to be run
		return;
	}
	public static function __post_option_process($obj = null)
	{
		// Passed is a string containing the name of the option after it has run
		return;
	}

	//
	// Installation Functions
	//

	public static function __pre_install_process($obj = null)
	{
		// Passed is the pts_test_install_manager
		return;
	}
	public static function __pre_test_download($obj = null)
	{
		// Passed is an array where as the first element is the test identifier for the name of the test about to have its files downloaded
		// The second element in this array is an additional array containing the pts_test_file_download objects that will be downloaded
		return;
	}
	public static function __interim_test_download($obj = null)
	{
		// Passed is an array where as the first element is the test identifier for the name of the test about to have its files downloaded
		// The second element in this array is an additional array containing the pts_test_file_download objects that will be downloaded
		return;
	}
	public static function __post_test_download($obj = null)
	{
		// Passed as the first argument to this function is the test identifier for the name of the test where the file(s) were just downloaded
		return;
	}
	public static function __pre_test_install($obj = null)
	{
		// Passed as the first argument to this function is the test install request for the test about to be installed
		return;
	}
	public static function __post_test_install($obj = null)
	{
		// Passed as the first argument to this function is the test install request for the test just installed
		return;
	}
	public static function __post_install_process($obj = null)
	{
		// Passed is the pts_test_install_manager
		return;
	}

	//
	// Run Functions
	//

	public static function __run_manager_setup($obj = null)
	{
		// Passed is the current pts_test_run_manager
		return;
	}
	public static function __pre_run_process($obj = null)
	{
		// Passed is the current pts_test_run_manager, where accessible is an array of pts_test_result objects for all tests scheduled to run
		return;
	}
	public static function __pre_test_run($obj = null)
	{
		// Passed is a read-only copy of the current pts_test_result for the given test
		return;
	}
	public static function __calling_test_script($obj = null)
	{
		// Passed is a read-only copy of the current pts_test_result for the given test
		return;
	}
	public static function __test_running($obj = null)
	{
		// This is called after proc_open() on the test and passed is the proc_open()'s resource, to obtain the PID, etc.
		return;
	}
	public static function __test_log_output($obj = null)
	{
		// This is called and passed to it is the output of the test run's log file location
		return;
	}
	public static function __interim_test_run($obj = null)
	{
		// Passed is a read-only copy of the current pts_test_result for the given test
		return;
	}
	public static function __post_test_run($obj = null)
	{
		// Passed is a read-only copy of the current pts_test_result for the given test
		return;
	}
	public static function __post_test_run_success($obj = null)
	{
		// Passed is a copy of the pts_test_result when the run was successful
		return;
	}
	public static function __test_run_success_inline_result($obj = null)
	{
		// Passed is a copy of the successful pts_test_result after showing other inline metrics
		return;
	}
	public static function __post_test_run_process($obj = null)
	{
		// Passed is a copy of the pts_result_file
		return;
	}
	public static function __post_run_process($obj = null)
	{
		// Passed is the current pts_test_run_manager, where accessible is an array of pts_test_result objects for all tests scheduled to run
		return;
	}
	public static function __post_test_run_system_logs($obj = null)
	{
		// Passed is a path to a directory containing the system logs for the current test run
		return;
	}

	//
	// Event-driven Functions
	//

	public static function __event_openbenchmarking_upload($obj = null)
	{
		// Passed is the decoded JSON response from OpenBenchmarking.org
		return;
	}
	public static function __event_results_process($obj = null)
	{
		// Passed is the pts_test_run_manager
		return;
	}
	public static function __event_results_saved($obj = null)
	{
		// Passed is a copy of the pts_test_run_manager
		return;
	}
	public static function __event_post_run_stats($obj = null)
	{
		// Passed is a copy of the pts_test_run_manager for showing any post-run statistics
		return;
	}
	public static function __event_user_error($obj = null)
	{
		// TODO use __event_user_error
		return;
	}
	public static function __event_pre_run_error($obj = null)
	{
		// Passed is an array with the test profile and error message. The pre-run errors are warnings/errors prior to actual test execution phase
		return;
	}
	public static function __event_run_error($obj = null)
	{
		// Passed is an array where the first element is a copy of the test_run_manager, the second elemnt is the test_run_request, and the third element is the error_msg string
		return;
	}
	public static function __event_reboot($obj = null)
	{
		// PTS is attempting to reboot the system
		// Passed is potentially the test run request of the test profile requesting the reboot as set by reboot-needed
		return;
	}

	*/
}

?>
