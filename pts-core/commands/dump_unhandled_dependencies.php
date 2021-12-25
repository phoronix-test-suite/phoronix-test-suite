<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2020, Phoronix Media
	Copyright (C) 2020, Michael Larabel

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

class dump_unhandled_dependencies implements pts_option_interface
{
	const doc_section = 'Debugging';
	const doc_description = 'This option will list missing entries in the external dependencies XML file for the operating system under test. This option is used if wanting to help find missing dependency XML data to fill in for contributing to upstream Phoronix Test Suite.';

	public static function run($r)
	{
		$x = pts_external_dependencies::vendor_identifier('package-list');
		echo PHP_EOL . pts_client::cli_just_bold('Vendor Identifier: ') . $x . PHP_EOL;
		$vendor_dependencies_parser = new pts_exdep_platform_parser($x);
		$covered_packages = $vendor_dependencies_parser->get_available_packages();
		$missing = 0;
		foreach(pts_external_dependencies::all_dependency_names() as $generic_name)
		{
			if(!in_array($generic_name, $covered_packages))
			{
				echo $generic_name . PHP_EOL;
				$missing++;
			}
		}
		if($missing == 0)
		{
			echo pts_client::cli_just_italic('No package entries missing.') . PHP_EOL;
		}
	}
}

?>
