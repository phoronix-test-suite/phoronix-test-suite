<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2018, Phoronix Media
	Copyright (C) 2008 - 2018, Michael Larabel

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

class install_test implements pts_option_interface
{
	const doc_section = 'Test Installation';
	const doc_use_alias = 'install';
	const doc_description = 'This option will install the selected test(s) inside the testing environment directory. The install process from downloading of the test files to the installation is fully automated. The install option needs to be supplied with the test name or suite as an argument. Optionally, a OpenBenchmarking.org ID or the name of a saved results file can be supplied as well and the test(s) to install will automatically be extracted from that information. If the test is already installed and was run by the latest version of the installation process, no action will be taken. Multiple arguments can be supplied to install additional tests at the same time.';

	public static function command_aliases()
	{
		return array('install');
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check('VARIABLE_LENGTH', array('pts_types', 'identifier_to_object'), null)
		);
	}
	public static function run($items_to_install)
	{
		pts_test_installer::standard_install($items_to_install);
	}
}

?>
