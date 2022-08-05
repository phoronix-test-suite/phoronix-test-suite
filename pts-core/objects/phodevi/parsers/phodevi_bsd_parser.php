<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2017, Phoronix Media
	Copyright (C) 2009 - 2017, Michael Larabel
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
		// Read sysctl, used by *BSDs and macOS
		$info = false;

		if(pts_client::executable_in_path('sysctl'))
		{
			$desc = pts_arrays::to_array($desc);

			for($i = 0; $i < count($desc) && empty($info); $i++)
			{
				$output = shell_exec('sysctl ' . $desc[$i] . ' 2>&1');
				if(empty($output))
				{
					continue;
				}

				if((($point = strpos($output, ':')) > 0 || ($point = strpos($output, '=')) > 0) && strpos($output, 'unknown oid') === false && strpos($output, 'is invalid') === false && strpos($output, 'not available') === false)
				{
					$info = trim(substr($output, $point + 1));
				}
			}
		}

		return $info;
	}
	public static function read_pciconf_by_class($class)
	{
		$entry = null;

		if(pts_client::executable_in_path('pciconf'))
		{
			$pciconf = pts_strings::trim_spaces(shell_exec('pciconf -lv 2> /dev/null'));
			if(($x = strpos($pciconf, 'class = ' . $class)) !== false)
			{
				$pciconf = substr($pciconf, 0, $x);
				$vendor = substr($pciconf, strrpos($pciconf, 'vendor =') + 8);
				$vendor = substr($vendor, 0, strpos($vendor, PHP_EOL));
				$vendor = trim(str_replace(array('\''), '', $vendor));
				if(($x = strrpos($pciconf, 'device =')) !== false)
				{
					$device = substr($pciconf, $x + 8);
					$device = substr($device, 0, strpos($device, PHP_EOL));
					$device = trim(str_replace(array('\'', '"'), '', $device));
				}
				else
				{
					$device = null;
				}

				$entry = trim($vendor . ' ' . $device);
			}
		}
		return $entry;
	}
	public static function read_kenv($v)
	{
		$ret = null;
		if(pts_client::executable_in_path('kenv'))
		{
			$kenv = shell_exec('kenv 2> /dev/null');

			$v = PHP_EOL . $v . '=';
			if(($x = strpos($kenv, $v)) !== false)
			{
				$ret = substr($kenv, ($x + strlen($v)));
				$ret = substr($ret, 0, strpos($ret, PHP_EOL));

				if($ret[0] == '"' && $ret[(strlen($ret) - 1)] == '"')
				{
					$ret = substr($ret, 1, -1);
				}
			}
		}

		return $ret != 'empty' ? $ret : null;
	}
	public static function read_acpiconf($desc)
	{
		$info = false;

		if(pts_client::executable_in_path('acpiconf 2> /dev/null'))
		{
			$output = shell_exec('acpiconf -i0');

			if(($point = strpos($output, $desc . ':')) !== false)
			{
				$info = substr($output, $point + strlen($desc) + 1);
				$info = substr($info, 0, strpos($info, "\n"));
				$info = trim($info);
			}
		}

		return $info;
	}
}

?>
