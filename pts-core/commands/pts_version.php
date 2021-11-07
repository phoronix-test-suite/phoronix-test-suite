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

class pts_version implements pts_option_interface
{
	const doc_section = 'Other';
	const doc_use_alias = 'version';
	const doc_description = 'This option will display the Phoronix Test Suite client version.';

	public static function command_aliases()
	{
		return array('version');
	}
	public static function run($r)
	{
		echo PHP_EOL . pts_core::program_title() . PHP_EOL;
	}
}

?>
