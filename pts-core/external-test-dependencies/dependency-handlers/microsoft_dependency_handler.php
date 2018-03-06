<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018, Phoronix Media
	Copyright (C) 2018, Michael Larabel

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

class microsoft_dependency_handler implements pts_dependency_handler
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
			switch($file)
			{
				case 'Rscript':
					// R
					$packages_needed[] = 'https://cran.r-project.org/bin/windows/base/R-3.4.3-win.exe';
					break;

			}
		}
		return $packages_needed;
	}
	public static function install_dependencies($os_packages_to_install)
	{
		$files_to_download = array();
		$pass_to_cygwin = array();

		foreach($os_packages_to_install as $pkg_line)
		{
			foreach(explode(' ', $pkg_line) as $item_check)
			{
				if(in_array(substr($item_check, 0, 4), array('http', 'ftp:')))
				{
					// File to download and install
					$files_to_download[] = $item_check;
				}
				else
				{
					// Assuming packages desired by Cygwin
					// TODO make better assumptions about this...
					$pass_to_cygwin[] = $item_check;
				}
			}
		}

		$cwd = getcwd();
		if(!empty($files_to_download))
		{
			$download_location =  getenv('USERPROFILE') . '\Downloads\\';
			echo PHP_EOL . 'Files needed for download to meet external dependencies...';
			echo PHP_EOL . 'Download Location: ' . $download_location . PHP_EOL;

			chdir($download_location);
			foreach($files_to_download as $url)
			{
				$download_destination = $download_location . basename($url);
				echo $url . PHP_EOL . ' - ' . $download_destination . PHP_EOL;
				if(is_file($download_destination))
				{
					echo 'File Already Present' . PHP_EOL;
				}
				else
				{
					echo 'Downloading...' . PHP_EOL;
					pts_network::download_file($url, $download_destination);
				}
				echo 'Executing...' . PHP_EOL;
				shell_exec(basename($url));
			}
		}
		if(!empty($pass_to_cygwin))
		{
			echo PHP_EOL . 'Cygwin dependencies needed: ' . implode(' ', $pass_to_cygwin) . PHP_EOL;
			$cygwin_location = getenv('USERPROFILE') . '\Downloads\cygwin-setup-x86_64.exe';
			if(!is_file($cygwin_location))
			{
				echo 'Downloading Cygwin...';
				pts_network::download_file('http://cygwin.com/setup-x86_64.exe', $cygwin_location);
			}
			chdir(dirname($cygwin_location));
			$cygwin_cmd = basename($cygwin_location) . ' -q ' . implode(' -P ', $pass_to_cygwin);
			echo PHP_EOL . 'RUNNING: ' . $cygwin_cmd;
			shell_exec($cygwin_cmd);

		}
		chdir($cwd);
	}
}


?>

