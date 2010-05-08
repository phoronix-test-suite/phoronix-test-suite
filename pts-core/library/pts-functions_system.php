<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel
	pts-functions_system.php: Include system functions.

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

function pts_hw_string($return_string = true)
{
	return phodevi::system_hardware($return_string);
}
function pts_sw_string($return_string = true)
{
	// TODO: port to Phodevi module similar to the pts_hw_string()
	// Returns string of software information
	$sw = array();

	$sw["OS"] = phodevi::read_property("system", "operating-system");
	$sw["Kernel"] = phodevi::read_property("system", "kernel") . " (" . phodevi::read_property("system", "kernel-architecture") . ")";
	$sw["Desktop"] = phodevi::read_property("system", "desktop-environment");
	$sw["Display Server"] = phodevi::read_property("system", "display-server");
	$sw["Display Driver"] = phodevi::read_property("system", "display-driver-string");
	$sw["OpenGL"] = phodevi::read_property("system", "opengl-driver");
	$sw["Compiler"] = phodevi::read_property("system", "compiler");
	$sw["File-System"] = phodevi::read_property("system", "filesystem");
	$sw["Screen Resolution"] = phodevi::read_property("gpu", "screen-resolution-string");

	$sw = pts_remove_unsupported_entries($sw);

	return pts_process_string_array($return_string, $sw);
}
function pts_remove_unsupported_entries($array)
{
	$clean_elements = array();

	foreach($array as $key => $value)
	{
		if($value != -1 && !empty($value))
		{
			$clean_elements[$key] = $value;
		}
	}

	return $clean_elements;
}
function pts_system_identifier_string()
{
	$components = array(phodevi::read_property("cpu", "model"), phodevi::read_name("motherboard"), phodevi::read_property("system", "operating-system"), phodevi::read_property("system", "compiler"));
	return base64_encode(implode("__", $components));
}
function pts_process_string_array($return_string, $array)
{
	if($return_string)
	{
		$return = "";

		foreach($array as $type => $value)
		{
			if($return != "")
			{
				$return .= ", ";
			}

			$return .= $type . ": " . $value;
		}
	}
	else
	{
		$return = $array;
	}

	return $return;
}

?>
