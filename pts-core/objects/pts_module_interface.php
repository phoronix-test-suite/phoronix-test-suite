<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2004-2008, Michael Larabel
	pts_module_interface.php: The generic Phoronix Test Suite module object that is extended by the specific modules/plug-ins

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

class pts_module_interface
{
	const module_name = "Generic Module";
	const module_version = "1.0.0";
	const module_description = "A description of the module.";
	const module_author = "Module Creator";

	public static $module_store_vars = array();

	public static function module_info()
	{

	}

	//
	// General Functions
	//

	public static function __startup($obj = NULL)
	{
		return;
	}
	public static function __shutdown($obj = NULL)
	{
		return;
	}

	//
	// Installation Functions
	//

	public static function __pre_install_process($obj = NULL)
	{
		return;
	}
	public static function __pre_test_install($obj = NULL)
	{
		return;
	}
	public static function __post_test_install($obj = NULL)
	{
		return;
	}
	public static function __post_install_process($obj = NULL)
	{
		return;
	}

	//
	// Run Functions
	//

	public static function __pre_run_process($obj = NULL)
	{
		return;
	}
	public static function __pre_test_run($obj = NULL)
	{
		return;
	}
	public static function __interim_test_run($obj = NULL)
	{
		return;
	}
	public static function __post_test_run($obj = NULL)
	{
		return;
	}
	public static function __post_run_process($obj = NULL)
	{
		return;
	}
}

?>
