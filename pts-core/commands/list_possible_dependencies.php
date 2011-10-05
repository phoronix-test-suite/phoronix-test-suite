<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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

class list_possible_dependencies implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will list all of the packages / external test dependencies that are are potentially used by the Phoronix Test Suite.';

	public static function run($r)
	{
		$all_dependencies = pts_external_dependencies::all_dependency_titles();
		pts_client::$display->generic_heading(count($all_dependencies) . ' External Dependencies Available');
		echo pts_user_io::display_text_list($all_dependencies);
		echo PHP_EOL;
	}
}

?>
