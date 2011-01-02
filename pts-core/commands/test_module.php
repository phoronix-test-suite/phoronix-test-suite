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

class test_module implements pts_option_interface
{
	const doc_section = 'Modules';
	const doc_description = 'This option can be used for debugging a Phoronix Test Suite module.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_module', 'is_module'), null)
		);
	}
	public static function run($r)
	{
		$module = strtolower($r[0]);
		pts_module_manager::attach_module($module);

		$processes = array('__startup', '__pre_option_process', '__pre_install_process', '__pre_test_download', '__interim_test_download', '__post_test_download', '__pre_test_install', '__post_test_install', '__post_install_process', '__pre_run_process', '__pre_test_run', '__interim_test_run', '__post_test_run', '__post_run_process', '__post_option_process', '__shutdown');

		foreach($processes as $process)
		{
			echo 'Calling: ' . $process . '()' . PHP_EOL;

			pts_module_manager::module_process($process);
			sleep(1);
		}
		echo PHP_EOL;
	}
}

?>
