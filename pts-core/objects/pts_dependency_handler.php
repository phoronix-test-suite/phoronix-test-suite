<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2018, Phoronix Media
	Copyright (C) 2015 - 2018, Michael Larabel

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

interface pts_dependency_handler
{
	//
	// This function is called when the Phoronix Test Suite client starts up for the first time to perform any **OS-specific** checks or double check any needed dependencies, etc are satisifed
	//
	public static function startup_handler();

	//
	// Passes a file name to the function, returned should be that distribution's package name for what provides that file
	//
	public static function what_provides($files_needed);

	//
	// A function called by pts_external_dependencies with a list of packages for that OS/distribution that should be installed.
	// This function is an alternative to using a bash script in external-dependencies/scripts
	//
	public static function install_dependencies($os_packages_to_install);
}

?>
