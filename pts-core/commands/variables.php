<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2021, Phoronix Media
	Copyright (C) 2021, Michael Larabel

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

class variables implements pts_option_interface
{
	const doc_section = 'User Configuration';
	const doc_description = 'This option will print all of the official environment variables supported by the Phoronix Test Suite for user configuration purposes. These environment variables are also listed as part of the official Phoronix Test Suite documentation while this command will also show the current value of the variables if currently set.';

	public static function run($r)
	{
		echo pts_env::get_documentation(true);
	}
}

?>
