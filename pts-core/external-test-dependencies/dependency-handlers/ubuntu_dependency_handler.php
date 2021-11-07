<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2020, Phoronix Media
	Copyright (C) 2015 - 2020, Michael Larabel

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

class ubuntu_dependency_handler implements pts_dependency_handler
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
			if(pts_client::executable_in_path('apt-file'))
			{
				if(!defined('APT_FILE_UPDATED'))
				{
					shell_exec('apt-file update 2>&1');
					define('APT_FILE_UPDATED', 1);
				}
				$added = false;

				// Try appending common paths
				if(strpos($file, '.h') !== false)
				{
					$apt_provides = self::run_apt_file_provides('/usr/include/' . $file);
					if($apt_provides != null)
					{
						$packages_needed[$file] = $apt_provides;
						$added = true;
					}
				}
				else if(strpos($file, '.so') !== false)
				{
					$apt_provides = self::run_apt_file_provides('/usr/lib/' . $file);
					if($apt_provides != null)
					{
						$packages_needed[$file] = $apt_provides;
						$added = true;
					}
				}
				else
				{
					foreach(array('/usr/bin/', '/bin/') as $possible_path)
					{
						$apt_provides = self::run_apt_file_provides($possible_path . $file);
						if($apt_provides != null)
						{
							$packages_needed[$file] = $apt_provides;
							$added = true;
							break;
						}
					}
				}

				// Broader search
				if(!$added && substr($file, 0, 3) == 'lib')
				{
					$apt_provides = self::run_apt_file_provides($file . '.so');
					if($apt_provides != null)
					{
						$packages_needed[$file] = $apt_provides;
						$added = true;
					}
				}
				if(!$added)
				{
					$apt_provides = self::run_apt_file_provides($file);
					if($apt_provides != null)
					{
						$packages_needed[$file] = $apt_provides;
						$added = true;
					}
				}
			}
		}
		return $packages_needed;
	}
	protected static function run_apt_file_provides($arg)
	{
		$apt_output = shell_exec('apt-file -N search --regex "' . $arg . '$" 2>/dev/null');

		if(strpos($apt_output, 'Pattern options:') !== false)
		{
			$apt_output = shell_exec('apt-file --regexp search "' . $arg . '$" 2>/dev/null');
		}

		if($apt_output == null)
		{
			return null;
		}

		foreach(explode(PHP_EOL, $apt_output) as $line)
		{
			if(($x = strpos($line, ': ')) == false || strpos($line, 'bash-completion') !== false || strpos($line, 'examples') !== false)
			{
				continue;
			}
			$proposed = trim(substr($line, 0, $x));
			if(strpos($proposed, '[') !== false || strpos($proposed, ']') !== false)
			{
				continue;
			}
			return $proposed;
		}

		return null;
	}
	public static function install_dependencies($os_packages_to_install)
	{
		// Not needed since this OS uses a dependency install script instead...
	}
}


?>
