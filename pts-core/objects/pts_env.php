<?php

/*
	Phoronix Test Suite
	Copyright (C) 2021, Phoronix Media
	Copyright (C) 2021, Michael Larabel

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

class pts_env
{
	protected static $overrides = array();
	protected static $env_vars = array(
		'NO_COLOR' => array(
			'description' => 'This option when enabled will force-disable the CLI/TUI text coloring. By default the Phoronix Test Suite will attempt to use CLI/TUI text colors and bolding of text for supported terminals.',
			'default' => '',
			'usage' => array('all'),
			'value_type' => 'bool',
			),
		'TERMINAL_WIDTH' => array(
			'description' => 'This option is used for overriding the detected default of the terminal width for the CLI/TUI interface.',
			'default' => '',
			'usage' => array('all'),
			'value_type' => 'positive_integer',
			),
		'PHODEVI_SANITIZE' => array(
			'description' => 'This option can be used for stripping out part of a string on Phodevi (Phoronix Device Interface) hardware/software properties. Namely around the reported hardware/software information in result files if wanting any values / portions of strings stripped out from that information, such as for confidential hardware strings or other privacy concerns, PHODEVI_SANITIZE can be set. The value will be removed from read Phodevi hardware/software properties if set. Multiple strings to search for can be set by delimiting with a comma. If wanting to limit the sanitization to a particular property, the property value can be specified such as [property]=[value] to sanitisze like a value of "motherboard=ABCVENDOR" or CPU=ENGINEERING-SAMPLE to delete those strings rather than simply the string to remove that will look for matches in any property."',
			'default' => '',
			'usage' => array('all'),
			'value_type' => 'string',
			'advertise_in_phoromatic' => true,
			'onchange' => 'phodevi::set_sanitize_string',
			),
		'PTS_SILENT_MODE' => array(
			'description' => 'This option when enabled will yield slightly less verbose Phoronix Test Suite terminal output by silencing unnecessary messages / prompts.',
			'default' => false,
			'usage' => array('all'),
			'value_type' => 'bool',
			),
		'PTS_DISPLAY_MODE' => array(
			'description' => 'If you wish to load a non-default display mode for a single instance, specify the mode in this variable as an alternative to adjusting the user configuration file.',
			'default' => '',
			'usage' => array('all'),
			'value_type' => 'enum',
			'enum' => array('BASIC', 'BATCH', 'CONCISE', 'SHORT', 'DEFAULT'),
			),
		'NO_PHODEVI_CACHE' => array(
			'description' => 'This option will disable use of the built-in Phodevi (Phoronix Device Interface) cache of system software/hardware details. When enabled, the information is not cached and will be re-computed on each query. This is mainly useful for debugging purposes.',
			'default' => false,
			'usage' => array('all'),
			'value_type' => 'bool',
			),
		'PTS_TEST_INSTALL_ROOT_PATH' => array(
			'description' => 'This option can be used for overriding where tests are installed to on the system. An absolute writable directory path can be the value if wanting to override the default (or user configuration file specified) test installation directory path.',
			'default' => '',
			'usage' => array('install', 'benchmark', 'stress_run'),
			'value_type' => 'string',
			),
		'TEST_RESULTS_NAME' => array(
			'description' => 'This option can be used for specifying the result file name for saving the test/benchmark results automatically to the given name.',
			'default' => '',
			'usage' => array('benchmark', 'stress_run'),
			'value_type' => 'string',
			),
		'TEST_RESULTS_IDENTIFIER' => array(
			'description' => 'This option can be used for specifying the result identifier for distinguishing this run within the saved result file.',
			'default' => '',
			'usage' => array('benchmark', 'stress_run'),
			'value_type' => 'string',
			),
		'TEST_RESULTS_DESCRIPTION' => array(
			'description' => 'This option can be used for specifying the result file description for saving that string and not be prompted for providing a description during the test execution process.',
			'default' => '',
			'usage' => array('benchmark', 'stress_run'),
			'value_type' => 'string',
			),
		'PTS_EXTRA_SYSTEM_LOGS_DIR' => array(
			'description' => 'By default the Phoronix Test Suite collects common system logs (cpuinfo, lscpu, dmesg) during the benchmarking process when saving test results. If wanting to collect additional, arbitrary system log files specific to your operating environment or for other niche system information, this option can be set as a path to a directory containing such log files. Prior to running the Phoronix Test Suite simply set PTS_EXTRA_SYSTEM_LOGS_DIR to the directory where any files should be captured from following test completion.',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'string',
			'advertise_in_phoromatic' => true,
			),
		'TEST_EXECUTION_SORT' => array(
			'description' => 'This option can be used for controlling the sort order that the test profiles / benchmarks are run in, whether sorted or not and in what manner.',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'enum',
			'enum' => array('none', 'random', 'dependencies', 'test-estimated-time', 'test-estimated-time-desc', 'test', 'default'),
			'advertise_in_phoromatic' => true,
			),
		'TEST_EXEC_PREPEND' => array(
			'description' => 'This option can be used if wanting to specify a binary (e.g. sudo, cgroup or other resource limiting binaries or performance counters) to be called as the binary pre-pended prior to running a test profile binary/script. This option is namely used for specialized use-cases.',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'string',
			),
		'FORCE_TIMES_TO_RUN' => array(
			'description' => 'This option can be used to override the default number of times a given test is run. Rather than being specified by the individual test profile, FORCE_TIMES_TO_RUN allows for specifying the number of times to run each benchmark.',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'positive_integer',
			'advertise_in_phoromatic' => true,
			),
		'FORCE_MIN_TIMES_TO_RUN' => array(
			'description' => 'This option is similar to FORCE_TIMES_TO_RUN but is used for specifying the minimum possible number of times to run. Unlike FORCE_TIMES_TO_RUN, the run count can still exceed this value if the deviation between results or other factors are too high.',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'positive_integer',
			'advertise_in_phoromatic' => true,
			),
		'FORCE_MIN_TIMES_TO_RUN_CUTOFF' => array(
			'description' => 'Used in conjunction with the FORCE_MIN_TIMES_TO_RUN, the FORCE_MIN_TIMES_TO_RUN_CUTOFF can be used for specifyingg the amount of time (in minutes) before foregoing additional runs. This allows cutting off the testing early if this time threshold has been reached.',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'positive_integer',
			),
		'FORCE_ABSOLUTE_MIN_TIMES_TO_RUN' => array(
			'description' => 'This option is similar to FORCE_MIN_TIMES_TO_RUN but is *absolute* in ensuring each test will run at least that number of times and not subject to change of any timed cut-offs or other factors.',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'positive_integer',
			),
		'FORCE_TIMES_TO_RUN_MULTIPLE' => array(
			'description' => 'This option is similar to FORCE_TIMES_TO_RUN but the value is a multiple for how many times the test profile should be run respective to its default value. If the value is set to 2 and a given test profile by default is set to run 3 times, it would now instead be run a total of 6 times. This can be used for increasing the statistical significance of test results by using a multiple of the default rather than a static number as is the case with FORCE_TIMES_TO_RUN.',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'positive_integer',
			),
		'IGNORE_RUNS' => array(
			'description' => 'This option can be used if wanting the Phoronix Test Suite to automatically toss out a specified result position when running a test profile multiple times. E.g. setting this value to 1 will toss out automatically the first run of each test profile or a value of 3 will toss out the third run of a given test. This overrides the IgnoreRuns option also available to individual test profiles. Multiple values for runs to ignore can be specified by delimiting with a comma.',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'string',
			),
		'FORCE_MIN_DURATION_PER_TEST' => array(
			'description' => 'This option can be used to specify the minimum number of times to run a given benchmark. Rather than relying on a static times-to-run count, the test will keep looping until the time has exceeded this number (in minutes).',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'positive_integer',
			'advertise_in_phoromatic' => true,
			),
		'PRESET_OPTIONS' => array(
			'description' => 'PRESET_OPTIONS can be used for seeding the values of test profile run options from the environment (though the preferred approach for pre-configuring tests in an automated manner would be by constructing your own local test suite).  For setting any test option(s) from an environment variable rather than being prompted for the options when running a test. Example: "PRESET_OPTIONS=\'stream.run-type=Add\' phoronix-test-suite benchmark stream".',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'string',
			),
		'PRESET_OPTIONS_VALUES' => array(
			'description' => 'This option is similar to PRESET_OPTIONS and uses the same syntax but rather than seeding the selected run option it uses the value verbatim as for what is passed to the test profile run option.',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'string',
			),
		'PTS_CONCURRENT_TEST_RUNS' => array(
			'description' => 'This option is used in the stress run/benchmarking mode to indicate the number of tests to run concurrently as part of the stress run process.',
			'default' => false,
			'usage' => array('stress_run'),
			'value_type' => 'positive_integer',
			),
		'TOTAL_LOOP_TIME' => array(
			'description' => 'This option is used to specify the amount of time (in minutes) to loop the testing during the Phoronix Test Suite stress run or normal benchmarking process.',
			'default' => '',
			'usage' => array('stress_run', 'benchmark'),
			'value_type' => 'positive_integer',
			),
		'TOTAL_LOOP_COUNT' => array(
			'description' => 'This option is used to specify a multiple if wishing to run each test multiple times rather than just once per saved result file.',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'positive_integer',
			),
		'LIMIT_ELAPSED_TEST_TIME' => array(
			'description' => 'This option can be used for limiting the amount of time the benchmarking process runs. The value specified is the number of minutes to allow for benchmarking. After a test finishes if that number of minutes has been exceeded, the testing process will abort early and not run any remaining tests.',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'positive_integer',
			'advertise_in_phoromatic' => true,
			),
		'DONT_BALANCE_TESTS_FOR_SUBSYSTEMS' => array(
			'description' => 'If this value is true, the Phoronix Test Suite stress-run manager will not attempt to distribute the selected test(s) among available hardware subsystems. For stress runs with tests covering multiple subsystems (e.g. CPU, GPU, RAM), the default behavior is try to ensure the tests to run concurrently are as balanced across the tested subsystems as possible.',
			'default' => false,
			'usage' => array('stress_run'),
			'value_type' => 'bool',
			),
		'DONT_TRY_TO_ENSURE_TESTS_ARE_UNIQUE' => array(
			'description' => 'When running in the stress-run mode, the default behavior will try to ensure when tests are running concurrently that as many unique tests as possible are being run. Setting this value to try will avoid that check and just attempt to truly randomize the tests being run concurrently without regard for trying to avoid duplicates.',
			'default' => false,
			'usage' => array('stress_run'),
			'value_type' => 'bool',
			),
		'OUTPUT_FILE' => array(
			'description' => 'When exporting a result file, this option can be used for specifying the file name / file path and name of where to save the exported result file to rather than assuming the user home directory.',
			'default' => '',
			'usage' => array('result_output'),
			'value_type' => 'string',
			),
		'OUTPUT_DIR' => array(
			'description' => 'When exporting a result file, this option can be used for specifying the writable directory path where the exported result files should be saved to. The file-name will be automatically generated.',
			'default' => '',
			'usage' => array('result_output'),
			'value_type' => 'string',
			),
		'GRAPH_HIGHLIGHT' => array(
			'description' => 'If automatically generating an HTML or PDF result file from the command-line and wanting to highlight desired result identifier(s), GRAPH_HIGHLIGHT can be set to a comma delimited list of result identifiers to highlight / color differently than the rest.',
			'default' => '',
			'usage' => array('result_output'),
			'value_type' => 'string',
			),
		'SORT_BY' => array(
			'description' => 'This option can be used for specifying the sort order for commands like auto-sort-result-file whether to sort by identifier name, test length, etc.',
			'default' => 'identifier',
			'usage' => array('auto_sort_result_file'),
			'value_type' => 'enum',
			'enum' => array('date', 'date-asc', 'date-desc', 'identifier'),
			),
		'NO_HTTPS' => array(
			'description' => 'Enable this option if wanting the Phoronix Test Suite when downloading resources to attempt to only use HTTP without any HTTPS connections. Note: some downloads may fail for servers that only support HTTPS.',
			'default' => false,
			'usage' => array('all'),
			'value_type' => 'bool',
			),
		'NO_DOWNLOAD_CACHE' => array(
			'description' => 'Enable this option if the Phoronix Test Suite should not attempt to discover and use any local/remote Phoronix Test Suite download cache when installing tests and attempting to find those files locally or on a LAN resource.',
			'default' => false,
			'usage' => array('install'),
			'value_type' => 'bool',
			),
		'NO_FILE_HASH_CHECKS' => array(
			'description' => 'Enable this option if you want to skip the MD5 / SHA256 file hash checks after downloading files with known MD5/SHA256 hashsums for verification. This is namely useful for select debugging scenarios and other situations where a file may have been trivially changed / re-packaged and wishing to still install a test even though the hash no longer matches until the test profile has been updated.',
			'default' => false,
			'usage' => array('install'),
			'value_type' => 'bool',
			),
		'SKIP_TEST_SUPPORT_CHECKS' => array(
			'description' => 'This debugging/validation option will have the Phoronix Test Suite skip any test support checks for a test profile (architecture compatibility, OS compatibility, etc) and just assume all tests are supported.',
			'default' => false,
			'usage' => array('install', 'benchmark'),
			'value_type' => 'bool',
			),
		'NO_COMPILER_MASK' => array(
			'description' => 'By default the Phoronix Test Suite attempts to determine the intended system code compilers (namely C / C++ / Fortran) and to intercept the arguments being passed to them during test installation in order to record the prominent compiler flags being used. If this behavior causes problems for your system, NO_COMPILER_MASK can be enabled for debugging purposes to avoid this compiler intercepting/symlinking behavior.',
			'default' => false,
			'usage' => array('install'),
			'value_type' => 'bool',
			),
		'NO_EXTERNAL_DEPENDENCIES' => array(
			'description' => 'Enabling this option will have the Phoronix Test Suite skip over attempting to detect and install any system/external dependencies needed to run desired test profiles. This should just be used in case of testing/evaluation purposes and may leave some tests unable to successfully build/install.',
			'default' => false,
			'usage' => array('install'),
			'value_type' => 'bool',
			),
		'SKIP_EXTERNAL_DEPENDENCIES' => array(
			'description' => 'Rather than NO_EXTERNAL_DEPENDENCIES to outright disable the Phoronix Test Suite external dependency handling, SKIP_EXTERNAL_DEPENDENCIES can be used with a value of a comma separated list of specific external dependencies to avoid. This is mostly useful for any external dependencies that may be out of date or fail to install on your platform.',
			'default' => '',
			'usage' => array('install'),
			'value_type' => 'string',
			),
		'PTS_DOWNLOAD_CACHE' => array(
			'description' => 'PTS_DOWNLOAD_CACHE can be used for setting a path to a directory on the system containing a Phoronix Test Suite download cache if located outside one of the default locations.',
			'default' => '',
			'usage' => array('install'),
			'value_type' => 'string',
			),
		'SKIP_TESTS' => array(
			'description' => 'SKIP_TESTS will skip the test installation and execution of any test identifiers specified by this option. Multiple test identifiers can be specified, delimited by a comma.',
			'default' => '',
			'usage' => array('install', 'benchmark'),
			'value_type' => 'string',
			),
		'SKIP_TESTS_HAVING_ARGS' => array(
			'description' => 'SKIP_TESTS_HAVING_ARGS will skip the test installation and execution of any tests where the specified test arguments match the given string. E.g. if wanting to skip all Vulkan tests in a result file but run just the OpenGL tests or similar where wanting to limit the tests being run from within a result file. Multiple values can be specified when delimited by a comma.',
			'default' => '',
			'usage' => array('install', 'benchmark'),
			'value_type' => 'string',
			),
		'SKIP_TESTING_SUBSYSTEMS' => array(
			'description' => 'This option is similar to SKIP_TESTS but allows for specifying hardware subsystems (e.g. Graphics) to skip from installing/running any test profiles beloning to that subsystem type. Multiple subsystems can be specified when delimited by a comma.',
			'default' => '',
			'usage' => array('install', 'benchmark'),
			'value_type' => 'string',
			),
		'PTS_MODULE_SETUP' => array(
			'description' => 'This option can be used for seeding a module\'s settings when running the phoronix-test-suite module-setup command. An example would be: "PTS_MODULE_SETUP=\'phoromatic.remote_host=http://www.phoromatic.com/; phoromatic.remote_account=123456; phoromatic.remote_verifier=ABCD\' phoronix-test-suite module-setup phoromatic".',
			'default' => '',
			'usage' => array('modules'),
			'value_type' => 'string',
			),
		'PTS_MODULES' => array(
			'description' => 'This option can be used for specifying a comma-separated list of Phoronix Test Suite modules to load at start-time, complementary to the modules specified in the user configuration file. PTS_MODULES is namely used for development purposes or wanting to temporarily enable a given module.',
			'default' => '',
			'usage' => array('modules'),
			'value_type' => 'string',
			),
		'PTS_IGNORE_MODULES' => array(
			'description' => 'Enabling this option can be used for temporarily disabling Phoronix Test Suite modules from being loaded on a given run. This is primarily for debugging purposes.',
			'default' => false,
			'usage' => array('modules'),
			'value_type' => 'bool',
			),
		'TEST_TIMEOUT_AFTER' => array(
			'description' => 'When this variable is set, the value will can be set to "auto" or a positive integer. The value indicates the number of minutes until a test run should be aborted, such as for a safeguard against hung/deadlocked processes or other issues. Setting this to a high number as a backup would be recommended for fending off possible hangs / stalls in the testing process if the test does not quit. If the value is "auto", it will quit if the time of a test run exceeds 3x the average time it normally takes the particular test to complete its run. In the future, auto might be enabled by default in a future PTS release. This functionality requires system PHP PCNTL support (i.e. no Windows support).',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'positive_integer',
			'module' => 'test_timeout',
			'advertise_in_phoromatic' => true,
			),
		'MONITOR' => array(
			'description' => 'This option can be used for system sensor monitoring during test execution. The Phoronix Test Suite system_monitor module can monitor various exposed sensors and record them as part of the result file and present them as additional graphs / metrics in the result viewer. The exposed sensors varies by platform hardware/software. This functionality also requires PHP PCNTL support and thus is not available for some platforms (i.e. Windows).',
			'default' => '',
			'usage' => array('benchmark'),
			'value_type' => 'enum_multi',
			'enum' => array('all', 'cpu.peak-freq', 'cpu.temp', 'cpu.power', 'cpu.usage', 'gpu.freq', 'gpu.power', 'gpu.temp', 'hdd.temp', 'memory.usage', 'swap.usage', 'sys.power', 'sys.temp'),
			'module' => 'system_monitor',
			'advertise_in_phoromatic' => true,
			),
		'LINUX_PERF' => array(
			'description' => 'This option allows providing additional complementary per-test graphs looking at various Linux perf subsystem metrics such as cache usage, instructions executed, and other metrics. This requires you to have Linux\'s perf user-space utility already installed and performance counter access.',
			'default' => false,
			'usage' => array('benchmark'),
			'value_type' => 'bool',
			'module' => 'linux_perf',
			'advertise_in_phoromatic' => true,
			),
		'TURBOSTAT_LOG' => array(
			'description' => 'This option allows attaching "turbostat" outputs to the end of archived benchmark/test log files if interested in the Linux TurboStat information. This assumes you have turbostat available on the Linux system(s) and have permissions (root) for running turbostat.',
			'default' => false,
			'usage' => array('benchmark'),
			'value_type' => 'bool',
			'module' => 'turbostat',
			'advertise_in_phoromatic' => true,
			),
		'WATCHDOG_SENSOR' => array(
			'description' => 'This option will enable the watchdog module that checks system sensor values pre/interim/post benchmark execution. If the selected sensor(s) exceed the static threshold level, testing will be paused before continuing to any additional tests so that the system can sleep. Ideally this will allow the system to return to a more suitable state before resuming testing after the sensor value is back below the threshold or after a pre-defined maximum time limit to spend sleeping. This module is mostly focused on pausing testing should system core temperatures become too elevated to allow time for heat dissipation.',
			'default' => false,
			'usage' => array('benchmark'),
			'value_type' => 'enum_multi',
			'enum' => array('cpu.temp', 'gpu.temp', 'hdd.temp', 'sys.temp'),
			'module' => 'watchdog',
			'advertise_in_phoromatic' => true,
			),
		'WATCHDOG_SENSOR_THRESHOLD' => array(
			'description' => 'Used in conjunction with the WATCHDOG_SENSOR option, the WATCHDOG_SENSOR_THRESHOLD specifies the threshold for the sensor reading when the testing should be paused (e.g. the Celsius cut-off temperature).',
			'default' => false,
			'usage' => array('benchmark'),
			'value_type' => 'positive_integer',
			'module' => 'watchdog',
			'advertise_in_phoromatic' => true,
			),
		'WATCHDOG_MAXIMUM_WAIT' => array(
			'description' => 'Used in conjunction with the WATCHDOG_SENSOR option, this is the maximum amount of time to potentially wait when the watchdog is triggered for surpassing the threshold value. The value is the maximum number of minutes to wait being above the threshold.',
			'default' => false,
			'usage' => array('benchmark'),
			'value_type' => 'positive_integer',
			'module' => 'watchdog',
			'advertise_in_phoromatic' => true,
			),
		'REMOVE_TESTS_OLDER_THAN' => array(
			'description' => 'This option with the cleanup module can be used for automatically un-installing/removing installed tests if they have not been run in a period of time. The value for REMOVE_TESTS_OLDER_THAN is the number of days the test can be installed without running until this module will clean-up/remove older tests.',
			'default' => false,
			'usage' => array('benchmark'),
			'value_type' => 'positive_integer',
			'module' => 'cleanup',
			'advertise_in_phoromatic' => true,
			),
		'REMOVE_TESTS_ON_COMPLETION' => array(
			'description' => 'When this option is set to true, installed test profiles will be automatically removed/uninstalled when they are no longer in the current test execution queue. This is used for saving disk space / resources by automatically removing installed tests after they have been executed. For more persistent behavior is the RemoveTestInstallOnCompletion option within the Phoronix Test Suite user configuration file.',
			'default' => false,
			'usage' => array('benchmark'),
			'value_type' => 'bool',
			'advertise_in_phoromatic' => true,
			),
		'LOG_CLI_OUTPUT' => array(
			'description' => '[EXPERIMENTAL] When this option is enabled, the Phoronix Test Suite standard output from the terminal will be logged to any relevant Phoronix Test Suite / Phoromatic log file. This is mainly useful for debugging purposes and if wishing to always archive the standard output as part of Phoronix Test Suite logs.',
			'default' => false,
			'usage' => array('all'),
			'value_type' => 'bool',
			'advertise_in_phoromatic' => true,
			'onchange' => 'pts_logger::update_log_cli_output_state',
			),
		'HOST_EXTRA_COMPATIBLE_ARCH' => array(
			'description' => 'This option allows to specify additional architecture support from host machine to being able to install tests that supports specific architectures only. It can be useful in systems where is available an user-space emulator (e.g. FEX-EMU for x86_64 applications on aarch64) to run binary tests that cannot be compiled natively.',
			'default' => false,
			'usage' => array('all'),
			'value_type' => 'string',
			),

		);

	public static function read($name, &$overrides = null, $fallback_value = false)
	{
		if(isset(self::$overrides[$name]))
		{
			return self::$overrides[$name];
		}

		return getenv($name);
	}
	public static function set($name, $value)
	{
		if(!isset(self::$env_vars[$name]))
		{
			// trigger_error($name . ' is not a recognized Phoronix Test Suite environment variable.', E_USER_NOTICE);
		}
		if(PTS_IS_CLIENT && isset(self::$env_vars[$name]['module']) && !pts_module_manager::is_module_attached(self::$env_vars[$name]['module']))
		{
			// Ensure module is loaded
			pts_module_manager::attach_module(self::$env_vars[$name]['module']);
		}
		if(PTS_IS_CLIENT && isset(self::$env_vars[$name]['onchange']) && !empty(self::$env_vars[$name]['onchange']) && is_callable(self::$env_vars[$name]['onchange']))
		{
			// Call the passed function with the value being set
			call_user_func(self::$env_vars[$name]['onchange'], $value);
		}

		self::$overrides[$name] = $value;
	}
	public static function set_array($to_set, $clear_overrides = false)
	{
		if($clear_overrides)
		{
			self::$overrides = array();
		}
		foreach($to_set as $name => $value)
		{
			self::set($name, $value);
		}
	}
	public static function get_overrides()
	{
		return self::$overrides;
	}
	public static function remove($name)
	{
		if(isset(self::$overrides[$name]))
		{
			unset(self::$overrides[$name]);
		}
	}
	public static function read_possible_vars($limit = false)
	{
		$possible_vars = self::$env_vars;
		if($limit)
		{
			if($limit == 'phoromatic')
			{
				$limit = array('advertise_in_phoromatic' => true);
			}
			if(is_array($limit))
			{
				foreach($possible_vars as $key => $var_check)
				{
					foreach($limit as $index => $desired_value)
					{
						if(!isset($possible_vars[$key][$index]) || $possible_vars[$key][$index] != $desired_value)
						{
							unset($possible_vars[$key]);
							break;
						}
					}
				}
			}
		}
		ksort($possible_vars);
		return $possible_vars;
	}
	public static function get_documentation($for_terminal = true)
	{
		$docs = '';
		foreach(pts_env::read_possible_vars() as $var => $data)
		{
			if($for_terminal)
			{
				$docs .= PHP_EOL . pts_client::cli_just_bold($var);
				if(pts_env::read($var))
				{
					$docs .= ': ' . pts_client::cli_colored_text(pts_env::read($var), 'green', true);
				}
				$docs .= PHP_EOL;
				$docs .= pts_client::cli_just_italic($data['description']) . PHP_EOL;
			}
			else
			{
				$docs .= PHP_EOL . '<h2>' . $var . '</h2>' . PHP_EOL;
				$docs .= '<p><em>' . $data['description'] . '</em></p>' . PHP_EOL;
			}

			if(isset($data['default']) && !empty($data['default']))
			{
				if($for_terminal)
				{
					$docs .= pts_client::cli_just_bold('Default Value: ') . $data['default'] . PHP_EOL;
				}
				else
				{
					$docs .= '<p><strong>Default Value:</strong> ' . $data['default'] . '</p>' . PHP_EOL;
				}
			}
			if(!$for_terminal)
			{
				$docs .= '<p>';
			}
			if(isset($data['value_type']) && !empty($data['value_type']))
			{
				$value_type = '';
				switch($data['value_type'])
				{
					case 'bool':
						$value_type = 'boolean (TRUE / FALSE)';
						break;
					case 'string':
						$value_type = 'string';
						break;
					case 'positive_integer':
						$value_type = 'positive integer';
						break;
					case 'enum':
					case 'enum_multi':
						$value_type = 'enumeration' . (isset($data['enum']) ? ' (' . implode(', ', $data['enum']) . ')' : '');
					if($data['value_type'] == 'enum_multi')
					{
						$value_type .= PHP_EOL . 'Multiple options can be supplied when delimited by a comma.';
					}
						break;
				}
				if(!empty($value_type))
				{
					$docs .= 'The value can be of type: ' . $value_type . '.' . PHP_EOL;
				}
			}
			if(isset($data['usage']) && !empty($data['usage']))
			{
				$usages = array();
				foreach($data['usage'] as $u)
				{
					switch($u)
					{
						case 'install':
							$usages[] = 'test installation';
							break;
						case 'benchmark':
							$usages[] = 'test execution / benchmarking';
							break;
						case 'stress_run':
							$usages[] = 'stress-run mode';
							break;
						case 'result_output':
							$usages[] = 'result output generation';
							break;
						case 'modules':
							$usages[] = 'modules';
							break;
					}
				}
				if(!empty($usages))
				{
					$docs .= 'The variable is relevant for: ' . implode(', ', $usages) . '.' . PHP_EOL;
				}
			}
			if(isset($data['module']) && !empty($data['module']))
			{
				$docs .= 'The variable depends upon functionality provided by the Phoronix Test Suite module: ' . $data['module'] . '.' . PHP_EOL;
			}
			if(!$for_terminal)
			{
				$docs .= '</p>';
			}
		}
		return $docs;
	}
	public static function get_html_options($limit = false, $preset_defaults = array())
	{
		$html = '';
		foreach(pts_env::read_possible_vars($limit) as $var => $data)
		{
			$html .= PHP_EOL . '<h3>' . $var . '</h3>' . PHP_EOL;
			$html .= '<p><em>' . $data['description'] . '</em></p>' . PHP_EOL;

			$default_value = isset($data['default']) && !empty($data['default']) ? $data['default'] : '';
			if(isset($_REQUEST[$var]))
			{
				$default_value = strip_tags($_REQUEST[$var]);
			}
			else if(isset($preset_defaults[$var]))
			{
				$default_value = $preset_defaults[$var];
			}
			$html .= '<p>';

			$enum = array();
			switch((isset($data['value_type']) ? $data['value_type'] : ''))
			{
				case 'bool':
					$enum = array('TRUE', 'FALSE');
					$default_value = strtoupper($default_value);
				case 'enum':
					if(isset($data['enum']))
					{
						$enum = $data['enum'];
					}
					$html .= '<select name="' . $var . '"><option value="0">[Not Set]</option>';
					foreach($enum as $e)
					{
						$html .= '<option value="' . $e . '"' . (strtoupper($default_value) == strtoupper($e) ? ' selected="selected"' : '') . '>' . $e . '</option>';
					}
					$html .= '</select>';
					break;
				case 'enum_multi':
					if(isset($data['enum']))
					{
						if(!empty($default_value) && !is_array($default_value))
						{
							$default_value = explode($default_value, ',');
						}
						foreach($data['enum'] as $e)
						{
							$html .= '<input type="checkbox" name="' . $var . '[]" value="' . $e . '" ' . (is_array($default_value) && in_array($e, $default_value) ? 'checked="checked"' : '') . ' /> ' . $e . '<br />';
						}
					}
					break;
				case 'positive_integer':
					$html .= '<input type="number" min="0" max="9999" step="1" name="' . $var . '" value="' . $default_value . '" />';
					break;
				case 'string':
				default:
					$html .= '<input name="' . $var . '" value="' . $default_value . '" />';
					break;
			}
			$html .= '</p>';
		}
		return $html;
	}
	public static function get_posted_options($limit = false)
	{
		$posted = array();
		foreach(pts_env::read_possible_vars($limit) as $var => $data)
		{
			if(isset($_REQUEST[$var]))
			{
				if(is_array($_REQUEST[$var]))
				{
					foreach($_REQUEST[$var] as &$rqv)
					{
						$rqv = strip_tags($rqv);
					}
					$v = implode(',', $_REQUEST[$var]);
				}
				else
				{
					$v = $_REQUEST[$var];
					if(!empty($v))
					{
						$invalid = false;
						foreach(pts_strings::safety_strings_to_reject() as $invalid_string)
						{
							if(stripos($v, $invalid_string) !== false)
							{
								$v = '';
								break;
							}
						}

						$v = strip_tags($v);
					}
				}
				if(!empty($v) && $v !== 0)
				{
					$posted[$var] = pts_strings::sanitize($v);
				}
			}
		}
		return $posted;
	}
}

?>
