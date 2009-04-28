<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	phodevi.php: The object for interacting with the PTS device framework

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

class phodevi
{
	// An example:
	// echo phodevi::read_property("memory", "physical-usage") . " " . phodevi::read_property("memory", "swap-usage") . " " . phodevi::read_property("memory", "total-usage");

	public static function read_name($device)
	{
		return phodevi::read_property($device, "identifier");
	}
	public static function read_property($device, $read_property)
	{
		static $device_cache = null;
		$value = false;

		if(method_exists("phodevi_" . $device, "read_property"))
		{
			eval("\$property = phodevi_" . $device . "::read_property(\$read_property);");

			$do_cache = $property->can_cache();

			if($do_cache && isset($device_cache[$device][$read_property]))
			{
				$value = $device_cache[$device][$read_property];
			}
			else
			{
				$dev_class = $property->get_device_object();
				$dev_function = $property->get_device_function();

				if(is_array($dev_function))
				{
					if(count($dev_function) > 1)
					{
						// TODO: support passing more than one argument
						$dev_function_pass = $dev_function[1];
					}

					$dev_function = $dev_function[0];
				}
				else
				{
					$dev_function_pass = null;
				}

				if(method_exists($dev_class, $dev_function))
				{

					eval("\$read_value = " . $dev_class . "::" . $dev_function . "(\$dev_function_pass);");

					$value = $read_value; // possibly add some sort of check here

					if($do_cache)
					{
						$device_cache[$device][$read_property] = $value;
					}
				}
			}
		}

		return $value;
	}
}

?>
