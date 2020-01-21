<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2020, Phoronix Media
	Copyright (C) 2009 - 2020, Michael Larabel

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

class validate_result_file implements pts_option_interface
{
	const doc_section = 'Asset Creation';
	const doc_description = 'This option can be used for validating a Phoronix Test Suite result file as being compliant against the OpenBenchmarking.org specification.';

	public static function run($r)
	{
		if(pts_results::is_saved_result_file($r[0]))
		{
			$result_file = new pts_result_file($r[0]);
			pts_client::$display->generic_heading($r[0]);
			$valid = $result_file->validate();

			if($valid == false)
			{
				echo PHP_EOL . 'Errors occurred parsing the main XML.' . PHP_EOL;
				pts_validation::process_libxml_errors();
				return false;
			}
			else
			{
				echo PHP_EOL . 'The result file is Valid.' . PHP_EOL;
			}
		}
	}
}

?>
