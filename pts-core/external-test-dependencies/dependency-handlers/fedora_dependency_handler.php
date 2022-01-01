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

class fedora_dependency_handler implements pts_dependency_handler
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
			if(pts_client::executable_in_path('dnf'))
			{
				$dnf_provides = self::run_dnf_provides($file);
				if($dnf_provides != null)
				{
					$packages_needed[$file] = $dnf_provides;
				}
				else
				{
					// Try appending common paths
					if(strpos($file, '.h') !== false)
					{
						$dnf_provides = self::run_dnf_provides('/usr/include/' . $file);
						if($dnf_provides != null)
						{
							$packages_needed[$file] = $dnf_provides;
						}
					}
					else if(strpos($file, '.so') !== false)
					{
						$dnf_provides = self::run_dnf_provides('/usr/lib/' . $file);
						if($dnf_provides != null)
						{
							$packages_needed[$file] = $dnf_provides;
						}
					}
					else
					{
						foreach(array('/usr/bin/', '/bin/', '/usr/sbin') as $possible_path)
						{
							$dnf_provides = self::run_dnf_provides($possible_path . $file);
							if($dnf_provides != null)
							{
								$packages_needed[$file] = $dnf_provides;
								break;
							}
						}
					}
				}
			}
		}
		return $packages_needed;
	}
	protected static function run_dnf_provides($arg)
	{
		$dnf_output = shell_exec('dnf --quiet provides ' . $arg . ' 2>/dev/null');

		if(empty($dnf_output))
		{
			return null;
		}

		foreach(explode(PHP_EOL, $dnf_output) as $line)
		{
			if(($x = strpos($line, ' : ')) == false)
			{
				continue;
			}
			$line = trim(substr($line, 0, $x));

			if(strpos($line, '-') !== false && strpos($line, '.') !== false && strpos($line, ' ') === false)
			{
				// Don't mess with the version/arch stuff, etc, so try to strip it off
				$offset = 0;
				while(($x = strpos($line, '-', $offset)) !== false)
				{
					if(isset($line[($x + 1)]) && is_numeric($line[($x + 1)]))
					{
						$line = substr($line, 0, $x);
						break;
					}
					$offset = $x + 1;
				}

				return $line;
			}
		}

		return null;
	}
	public static function install_dependencies($os_packages_to_install)
	{
		// Not needed since this OS uses a dependency install script instead...
	}
}


?>
