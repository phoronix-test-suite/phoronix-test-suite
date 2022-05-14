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

class opensuse_dependency_handler implements pts_dependency_handler
{
	public static function startup_handler()
	{
		// Nothing to do
	}
	public static function what_provides($files_needed)
	{
		$packages_needed = array();
		foreach(pts_arrays::to_array($files_needed) as $file)
		{
			if(pts_client::executable_in_path('zypper'))
			{
				$zypper_provides = self::run_zypper_provides($file);
				if($zypper_provides == null && strlen($file) > 3)
				{
					if(substr($file, -2) == '.h')
					{
						$zypper_provides = self::run_zypper_provides('/usr/include/' . $file);
					}
					else if(substr($file, -3) == '.so')
					{
						$zypper_provides = self::run_zypper_provides('/usr/lib64/' . $file);
					}
					else
					{
						foreach(array('/usr/bin/', '/usr/sbin/', '/sbin/') as $b_path)
						{
							$zypper_provides = self::run_zypper_provides($b_path . $file);
							if($zypper_provides != null)
								break;
						}
					}
				}
				if($zypper_provides != null)
				{
					$packages_needed[$file] = $zypper_provides;
				}
			}
		}
		return $packages_needed;
	}
	protected static function run_zypper_provides($arg)
	{
		$line = shell_exec('zypper search --provides --match-exact ' . $arg . ' 2>/dev/null');

		if(($x = strpos($line, '-----')) == false)
		{
			return null;
		}
		$line = substr($line, $x);
		$line = substr($line, strpos($line, "\n") + 2);
		$line = trim(substr($line, 0, strpos($line, PHP_EOL)));
		$parts = explode('|', $line);

		if(isset($parts[1]))
		{
			return trim($parts[1]);
		}
		return null;
	}
	public static function install_dependencies($os_packages_to_install)
	{
		// Not needed since this OS uses a dependency install script instead...
	}
}


?>
