<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel

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

class list_missing_dependencies implements pts_option_interface
{
	const doc_section = 'Information';
	const doc_description = 'This option will list all of the packages / external test dependencies that are missing from the system that the Phoronix Test Suite may potentially need by select test profiles.';

	public static function run($r)
	{
		$missing_titles = pts_external_dependencies::missing_dependency_titles();
		pts_client::$display->generic_heading(count($missing_titles) . ' of ' . count(pts_external_dependencies::all_dependency_names()) . ' External Dependencies Missing');
		echo pts_user_io::display_text_list($missing_titles);
		echo PHP_EOL;
	}
}

?>
