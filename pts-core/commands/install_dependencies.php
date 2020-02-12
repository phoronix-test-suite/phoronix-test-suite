<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2020, Phoronix Media
	Copyright (C) 2008 - 2020, Michael Larabel

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

class install_dependencies implements pts_option_interface
{
	const doc_section = 'Test Installation';
	const doc_description = 'This option will install the external dependencies needed by the selected test(s) using the distribution\'s package management system. For example, some tests depend upon GCC for compiling code. If GCC is not detected on the system, the Phoronix Test Suite will attempt to install GCC using the distribution\'s package management system. If you are running this command as a local user, you may be prompted for the root password while the process is running. For unsupported distributions, the dependency names will be displayed along with common names for the package. The install-dependencies option needs to be supplied with the test name or suite as an argument. When using the install option, the external dependencies are automatically checked.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check('VARIABLE_LENGTH', array('pts_types', 'identifier_to_object'), null)
		);
	}
	public static function run($r)
	{
		$test_profiles = pts_types::identifiers_to_test_profile_objects($r, true, true);
		pts_external_dependencies::install_dependencies($test_profiles, false, false, true);
	}
}

?>
