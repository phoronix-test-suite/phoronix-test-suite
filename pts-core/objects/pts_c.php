<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2014, Phoronix Media
	Copyright (C) 2010 - 2014, Michael Larabel

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

class pts_c
{
	public static $test_flags = 0;

	const auto_mode = 2;
	const batch_mode = 4;
	const defaults_mode = 8;
	const debug_mode = 16;
	const remote_mode = 32;

	const force_install = 64;
	const is_recovering = 128;
	const is_run_process = 256;
	const skip_tests_with_missing_dependencies = 512;
}

?>
