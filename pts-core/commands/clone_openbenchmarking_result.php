<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2015, Phoronix Media
	Copyright (C) 2008 - 2015, Michael Larabel

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

class clone_openbenchmarking_result implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_use_alias = 'clone-result';
	const doc_description = 'This option will download a local copy of a file that was saved to OpenBenchmarking.org, as long as a valid public ID is supplied.';

	public static function command_aliases()
	{
		return array('clone', 'clone_result');
	}
	public static function argument_checks()
	{
		return array(
		new pts_argument_check('VARIABLE_LENGTH', array('pts_openbenchmarking', 'is_openbenchmarking_result_id'), null)
		);
	}
	public static function run($args)
	{
		$result_files = array();
		foreach($args as $id)
		{
			$xml = pts_openbenchmarking::clone_openbenchmarking_result($id, true);
			if($xml)
			{
				$result_file = new pts_result_file($xml);
				pts_client::save_test_result($id . '/composite.xml', $result_file->get_xml(), true);
				echo PHP_EOL . 'Result Saved To: ' . PTS_SAVE_RESULTS_PATH . $id . '/composite.xml' . PHP_EOL;
			}
		}
	}
}

?>
