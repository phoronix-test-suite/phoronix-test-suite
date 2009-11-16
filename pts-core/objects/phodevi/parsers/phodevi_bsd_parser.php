<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel
	phodevi_bsd_parser.php: General parsing functions specific to BSD

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

class phodevi_bsd_parser
{
	public static function read_sysctl($desc)
	{
		// Read sysctl, used by *BSDs
		$info = false;

		if(pts_executable_in_path("sysctl"))
		{
			$desc = pts_to_array($desc);

			for($i = 0; $i < count($desc) && empty($info); $i++)
			{
				$output = shell_exec("sysctl " . $desc[$i] . " 2>&1");

				if((($point = strpos($output, ":")) > 0 || ($point = strpos($output, "=")) > 0) && strpos($output, "unknown oid") === false && strpos($output, "is invalid") === false && strpos($output, "not available") === false)
				{
					$info = trim(substr($output, $point + 1));
				}
			}
		}

		return $info;
	}
}

?>
