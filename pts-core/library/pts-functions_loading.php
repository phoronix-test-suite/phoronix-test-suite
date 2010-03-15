<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions_loading.php: Basic functions for loading parts of the Phoronix Test Suite

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

function pts_load_function_set($title)
{
	$includes_file = PTS_LIBRARY_PATH . "pts-includes-" . $title . ".php";
	$functions_file = PTS_LIBRARY_PATH . "pts-functions_" . $title . ".php";

	return (is_file($includes_file) && include_once($includes_file)) || (is_file($functions_file) && include_once($functions_file));
}
function pts_load_run_option($option)
{
	if(is_file(COMMAND_OPTIONS_DIR . $option . ".php"))
	{
		if(!class_exists($option, false))
		{
			include(COMMAND_OPTIONS_DIR . $option . ".php");
		}

		if(method_exists($option, "required_function_sets"))
		{
			$required_function_sets = call_user_func(array($option, "required_function_sets"));

			foreach($required_function_sets as $to_load)
			{
				pts_load_function_set($to_load);
			}
		}
	}
}
function pts_load_object($to_load)
{
	if(class_exists($to_load))
	{
		return;
	}

	static $sub_objects = null;

	if($sub_objects == null)
	{
		$sub_objects = array();

		foreach(array_merge(glob(PTS_PATH . "pts-core/objects/*/*.php"), glob(PTS_PATH . "pts-core/objects/*/*/*.php")) as $file)
		{
			$object_name = basename($file, ".php");
			$sub_objects[$object_name] = $file;
		}
	}

	if(is_file(PTS_PATH . "pts-core/objects/" . $to_load . ".php"))
	{
		include(PTS_PATH . "pts-core/objects/" . $to_load . ".php");
	}
	else if(isset($sub_objects[$to_load]))
	{
		include($sub_objects[$to_load]);
		unset($sub_objects[$to_load]);
	}
}

?>
