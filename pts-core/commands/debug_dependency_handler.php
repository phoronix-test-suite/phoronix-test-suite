<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

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

class debug_dependency_handler implements pts_option_interface
{
	const doc_section = 'Other';
	const doc_description = 'This option is used for testing the distribution-specific dependency handler for external dependencies.';

	public static function run($r)
	{
		$exdep_parser = new pts_exdep_generic_parser();
		foreach($exdep_parser->get_available_packages() as $pkg)
		{
			$pkg_data = $exdep_parser->get_package_data($pkg);
			if(!isset($pkg_data['file_check']) || empty($pkg_data['file_check']))
			{
				continue;
			}
			$files = explode(' ', str_replace(array(' OR ', ', '), ' ', $pkg_data['file_check']));
			foreach($files as $file)
			{
				echo (is_array($file) ? implode(' ', $file) : $file) . ': ';
				$deps = pts_external_dependencies::packages_that_provide($file);
				echo (is_array($deps) ? implode(' ', $deps) : null) . PHP_EOL;
			}
		}
	}
}

?>
