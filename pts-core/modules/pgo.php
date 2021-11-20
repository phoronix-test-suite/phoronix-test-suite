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

class pgo extends pts_module_interface
{
	const module_name = 'Benchmarking Compiler PGO Impact';
	const module_version = '1.0.0';
	const module_description = 'This module makes it easy to test a compiler PGO (Profile Guided Optimization) performance impact by running a test without PGO optimizations, capturing the PGO profile, rebuilding the tests with the PGO profile generated, and then repeat the benchmarks.';
	const module_author = 'Michael Larabel';

	protected static $phase = '';
	protected static $pgo_storage_dir = '';
	protected static $stock_cflags = '';
	protected static $stock_cxxflags = '';

	public static function user_commands()
	{
		return array('benchmark' => 'pgo_benchmark');
	}
	public static function pgo_benchmark($to_run)
	{
		self::$pgo_storage_dir = pts_client::create_temporary_directory('pgo', true);
		echo 'PGO directory is: ' . self::$pgo_storage_dir . PHP_EOL;
		self::$stock_cflags = getenv('CFLAGS');
		self::$stock_cxxflags = getenv('CXXFLAGS');

		// make the initial run manager, collect the result file data we'll need, and run the tests pre-PGO...
		$run_manager = new pts_test_run_manager();
		$save_name = $run_manager->prompt_save_name();
		$result_identifier = $run_manager->prompt_results_identifier();
		$run_manager->do_skip_post_execution_options();

		// Also force a fresh install before doing any of the PGO-related args...
		self::$phase = 'PRE_PGO';
		pts_test_installer::standard_install($to_run, true);

		// run the tests saving PRE-PGO results
		$run_manager->standard_run($to_run);

		// force install of tests with PGO generation bits...
		self::$phase = 'GENERATE_PGO';

		// at least some say serial make ends up being better for PGO generation to not confuse the PGO process, the below override ensures -j 1
		pts_client::override_pts_env_var('NUM_CPU_CORES', 1);
		pts_client::override_pts_env_var('NUM_CPU_JOBS', 1);

		pts_test_installer::standard_install(array($save_name), true);

		// restore env vars about CPU core/jobs count
		pts_client::unset_pts_env_var_override('NUM_CPU_CORES');
		pts_client::unset_pts_env_var_override('NUM_CPU_JOBS');

		// run the tests one time each, not saving the results, in order to generate the PGO profiles...
		pts_env::set('FORCE_TIMES_TO_RUN', 1);
		$run_manager = new pts_test_run_manager(array('SaveResults' => false, 'RunAllTestCombinations' => false), true);
		$run_manager->standard_run(array($save_name));
		pts_env::remove('FORCE_TIMES_TO_RUN');

		// force re-install of tests, in process set PGO using bits -fprofile-dir=/data/pgo -fprofile-use=/data/pgo -fprofile-correction
		self::$phase = 'USE_PGO';
		pts_client::override_pts_env_var('NUM_CPU_CORES', 1);
		pts_client::override_pts_env_var('NUM_CPU_JOBS', 1);
		pts_test_installer::standard_install(array($save_name), true);
		pts_client::unset_pts_env_var_override('NUM_CPU_CORES');
		pts_client::unset_pts_env_var_override('NUM_CPU_JOBS');

		// run the tests saving results with " - PGO" postfix
		$run_manager = new pts_test_run_manager(array('UploadResults' => false, 'SaveResults' => true, 'PromptForTestDescription' => false, 'RunAllTestCombinations' => false, 'PromptSaveName' => true, 'PromptForTestIdentifier' => true, 'OpenBrowser' => true), true);
		$run_manager->set_save_name($save_name, false);
		$run_manager->set_results_identifier($result_identifier . ' - PGO');
		$run_manager->standard_run(array($save_name));

		// remove PGO files
		pts_file_io::delete(self::$pgo_storage_dir);

	}
	public static function __pre_test_install($test_install_request)
	{
		$pgo_dir = self::$pgo_storage_dir . $test_install_request->test_profile->get_identifier() . '/';
		pts_file_io::mkdir($pgo_dir);

		switch(self::$phase)
		{
			case 'PRE_PGO':
				break;
			case 'GENERATE_PGO':
				putenv('CFLAGS=' . self::$stock_cflags . ' -fprofile-dir=' . $pgo_dir . ' -fprofile-generate=' . $pgo_dir);
				putenv('CXXFLAGS=' . self::$stock_cxxflags . ' -fprofile-dir=' . $pgo_dir . ' -fprofile-generate=' . $pgo_dir);
				break;
			case 'USE_PGO':
				putenv('CFLAGS=' . self::$stock_cflags . ' -fprofile-dir=' . $pgo_dir . ' -fprofile-use=' . $pgo_dir . ' -fprofile-correction');
				putenv('CXXFLAGS=' . self::$stock_cxxflags . ' -fprofile-dir=' . $pgo_dir . ' -fprofile-use=' . $pgo_dir . ' -fprofile-correction');
				break;
		}
	}
}
?>
