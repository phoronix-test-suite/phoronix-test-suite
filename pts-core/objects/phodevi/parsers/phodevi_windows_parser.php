<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2018, Phoronix Media
	Copyright (C) 2009 - 2018, Michael Larabel
	phodevi_windows_parser.php: General parsing functions specific to the Windows OS

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

class phodevi_windows_parser
{

	public static function get_wmi_object($object, $name, $to_array = false)
	{
		$wmi_output = shell_exec('powershell -NoProfile "$obj = Get-WmiObject ' . $object . '; echo $obj.' . $name . '"');
		if($wmi_output != null)
		{
			$wmi_output = strpos($wmi_output, 'Invalid') == false ? trim($wmi_output) : '';
		}
		if($to_array)
		{
			$wmi_output = explode("\n", $wmi_output);
		}
		else if(($x = strpos($wmi_output, "\n")) !== false)
		{
			$wmi_output = substr($wmi_output, 0, $x);
		}
		return $wmi_output;
	}
	public static function get_wmi_object_multi($object, $name)
	{
		$wmi_output = trim(shell_exec('powershell -NoProfile "Get-WmiObject ' . $object . '"'));
		$matches = array();
		foreach(explode("\n", $wmi_output) as $line)
		{
			$line = explode(' : ', $line);
			if(trim($line[0]) == $name && isset($line[1]))
			{
				$matches[] = trim($line[1]);
			}
		}

		return $matches;
	}
}

?>
