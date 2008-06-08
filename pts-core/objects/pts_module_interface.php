<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2004-2008, Michael Larabel
	pts_module_interface.php: The generic Phoronix Test Suite module object that is extended by the specific modules/plug-ins
*/

class pts_module_interface
{
	const module_name = "Generic Module";
	const module_version = "1.0.0";
	const module_description = "A description of the module.";
	const module_author = "Module Creator";

	//
	// General Functions
	//

	public static function __startup($obj)
	{
		return;
	}
	public static function __shutdown($obj)
	{
		return;
	}

	//
	// Installation Functions
	//

	public static function __pre_install_process($obj)
	{
		return;
	}
	public static function __pre_test_install($obj)
	{
		return;
	}
	public static function __post_test_install($obj)
	{
		return;
	}
	public static function __post_install_process($obj)
	{
		return;
	}

	//
	// Run Functions
	//

	public static function __pre_run_process($obj)
	{
		return;
	}
	public static function __pre_test_run($obj)
	{
		return;
	}
	public static function __interim_test_run($obj)
	{
		return;
	}
	public static function __post_test_run($obj)
	{
		return;
	}
	public static function __post_run_process($obj)
	{
		return;
	}
}

?>
