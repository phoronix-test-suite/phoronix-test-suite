<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2011 - 2014, Phoronix Media
	Copyright (C) 2011 - 2014, Michael Larabel

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

class download_test_files implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = 'This will download the selected test file(s) to the Phoronix Test Suite download cache but will not install the tests.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check('VARIABLE_LENGTH', array('pts_types', 'identifier_to_object'), null)
		);
	}
	public static function run($r)
	{
		$test_profiles = pts_types::identifiers_to_test_profile_objects($r, true, true);

		if(count($test_profiles) > 0)
		{
			echo PHP_EOL . 'Downloading Test Files For: ' . implode(' ', $test_profiles);
			pts_test_installer::only_download_test_files($test_profiles, pts_env::read('PTS_DOWNLOAD_CACHE'));
		}
		else
		{
			echo PHP_EOL . 'Nothing found to download.' . PHP_EOL;
		}
	}
}

?>
