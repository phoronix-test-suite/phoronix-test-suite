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

class openbenchmarking_uploads implements pts_option_interface
{
	const doc_section = 'OpenBenchmarking.org';
	const doc_description = 'This option will list any recent test result uploads from the system\'s IP address to OpenBenchmarking.org.';

	public static function run($r)
	{
		if(count($result_uploads = pts_openbenchmarking::result_uploads_from_this_ip()) > 0)
		{
			echo PHP_EOL . pts_client::cli_just_bold('Recent Results Uploaded From This IP:') . PHP_EOL;
			$t = array();
			foreach($result_uploads as $id => $title)
			{
				$t[] = array(pts_client::cli_colored_text($id, 'green', true), $title);
			}
			echo pts_user_io::display_text_table($t) . PHP_EOL . PHP_EOL;
		}
		else
		{
			echo PHP_EOL . PHP_EOL . pts_client::cli_just_bold('No results found on OpenBenchmarking.org from this IP address.') . PHP_EOL . PHP_EOL . PHP_EOL;
		}
	}
}

?>
