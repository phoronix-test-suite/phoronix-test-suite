<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions.php: Include functions required for Phoronix Test Suite operation.

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

require(PTS_PATH . "pts-core/functions/pts.php");
require(PTS_PATH . "pts-core/functions/pts-interfaces.php");
require(PTS_PATH . "pts-core/functions/pts-functions_loading.php");
require(PTS_PATH . "pts-core/functions/pts-functions_directories.php");

if(PTS_MODE == "CLIENT" || defined("PTS_AUTO_LOAD_OBJECTS"))
{
	function __autoload($to_load)
	{
		pts_load_object($to_load);
	}
}
if(PTS_MODE == "LIB")
{
	// If a program using PTS as a library wants any of the below functions, they will need to load it manually
	return;
}

// Client Work
require(PTS_PATH . "pts-core/functions/pts-init.php");
require(PTS_PATH . "pts-core/functions/pts-functions_basic.php");
require(PTS_PATH . "pts-core/functions/pts-functions_client.php");
require(PTS_PATH . "pts-core/functions/pts-functions_pcqs.php");

// Load Main Functions
require(PTS_PATH . "pts-core/functions/pts-functions_io.php");
require(PTS_PATH . "pts-core/functions/pts-functions_shell.php");
require(PTS_PATH . "pts-core/functions/pts-functions_config.php");
require(PTS_PATH . "pts-core/functions/pts-functions_system.php");
require(PTS_PATH . "pts-core/functions/pts-functions_global.php");
require(PTS_PATH . "pts-core/functions/pts-functions_tests.php");
require(PTS_PATH . "pts-core/functions/pts-functions_types.php");
require(PTS_PATH . "pts-core/functions/pts-functions_vars.php");
require(PTS_PATH . "pts-core/functions/pts-functions_modules.php");
require(PTS_PATH . "pts-core/functions/pts-functions_assignments.php");

?>
