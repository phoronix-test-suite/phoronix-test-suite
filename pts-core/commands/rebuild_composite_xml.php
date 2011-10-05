<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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

class rebuild_composite_xml implements pts_option_interface
{
	const doc_section = 'Other';
	const doc_description = 'This option can be used to rebuild your composite XML file in the event that the main result file was corrupted and the test-X.xml files are still present in the result file directory.';

	public static function argument_checks()
	{
		return array(
		new pts_argument_check(0, array('pts_types', 'is_result_file'), null)
		);
	}
	public static function run($r)
	{
		$identifier = $r[0];
		$test_xml_files = pts_file_io::glob(PTS_SAVE_RESULTS_PATH . $identifier . '/test-*.xml');

		if(count($test_xml_files) == 0)
		{
			echo PHP_EOL . 'No test XML data was found.' . PHP_EOL;
			return false;
		}

		pts_client::save_test_result($identifier . '/composite.xml', pts_merge::merge_test_results_array($test_xml_files));
		pts_client::regenerate_graphs($identifier, 'The ' . $identifier . ' result file XML has been rebuilt.');
	}
}

?>
