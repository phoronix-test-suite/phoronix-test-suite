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

class backup extends pts_module_interface
{
	const module_name = 'Backup Creation + Restore';
	const module_version = '1.0.0';
	const module_description = 'This is a module for creating backups of the Phoronix Test Suite / Phoromatic and allows for restoring of created backups. The backup will be in ZIP or TAR format. If only a path is specified, the filename will be auto-generated with a current timestamp.';
	const module_author = 'Phoronix Media';

	public static function user_commands()
	{
		return array('create' => 'create_backup', 'restore' => 'restore_backup');
	}
	public static function create_backup($r)
	{
		if(!isset($r[0]) || empty($r[0]))
		{
			echo PHP_EOL . pts_client::cli_just_bold('ERROR: ') . 'You must pass the name of the backup file to create and/or any absolute path for the said file you would like to create.' . PHP_EOL . PHP_EOL;
			return false;
		}
		$backup_location = $r[0];
		if(strpos($backup_location, DIRECTORY_SEPARATOR) === false)
		{
			$backup_location = getcwd() . DIRECTORY_SEPARATOR . $backup_location;
		}
		if(is_dir($backup_location))
		{
			$backup_location .= 'pts-backup-' . date('Y-m-d-H-i-s');
		}
		$file_extension = phodevi::is_windows() ? 'zip' : 'tar';
		if(substr($backup_location, -4) != '.' . $file_extension)
		{
			$backup_location .= '.' . $file_extension;
		}
		echo PHP_EOL . pts_client::cli_just_bold('Proposed Backup File:') . ' ' . $backup_location . PHP_EOL . PHP_EOL;

		if(!is_writable(($dir = dirname($backup_location))))
		{
			echo pts_client::cli_just_bold('ERROR:') . ' This location does not appear writable: ' . $dir . PHP_EOL . PHP_EOL;
			return false;
		}

		echo PHP_EOL . 'Making download-cache to cache downloaded test files...' . PHP_EOL;
		pts_client::execute_command('make_download_cache');

		$root_backup_temp_dir = pts_client::create_temporary_directory(null, true);
		pts_file_io::mkdir(($backup_temp_dir = $root_backup_temp_dir . 'pts-backup' . DIRECTORY_SEPARATOR));
		$backup_manifest = array();

		$to_backup = self::backup_map();

		echo PHP_EOL;
		foreach($to_backup as $source => $dest)
		{
			echo pts_client::cli_just_bold('Backing Up:') . ' ' . $source . PHP_EOL;
			$success = pts_file_io::copy($source, $backup_temp_dir . $dest);
			if(!$success)
			{
				echo PHP_EOL . 'There may have been problems backing up: ' . $source . PHP_EOL;
			}
		}


		$manifest = self::dir_checks($backup_temp_dir, $backup_temp_dir);
		file_put_contents($backup_temp_dir . 'pts-backup-manifest.txt', $manifest);
		if($file_extension == 'zip')
		{
			pts_compression::zip_archive_create($backup_location, $backup_temp_dir);
		}
		else
		{
			pts_compression::compress_to_archive($backup_temp_dir, $backup_location);
		}
		echo pts_client::cli_just_bold('Backup File Written To: ') . $backup_location . PHP_EOL;
		echo pts_client::cli_just_bold('SHA1: ') . sha1_file($backup_location) . PHP_EOL;
		echo pts_client::cli_just_bold('File Size: ') . round(filesize($backup_location) / 1000000, 1) . ' MB' . PHP_EOL;
		pts_file_io::delete($root_backup_temp_dir, null, true);
	}
	protected static function backup_map()
	{
		return array(
			// User configuration
			pts_config::get_config_file_location() => 'phoronix-test-suite.xml',
			// test results
			PTS_SAVE_RESULTS_PATH => 'test-results',
			// test profiles
			PTS_TEST_PROFILE_PATH => 'test-profiles',
			// test suites
			PTS_TEST_SUITE_PATH => 'test-suites',
			// modules data
			pts_module::module_data_path() => 'modules-data',
			// Phoromatic
			phoromatic_server::phoromatic_path() => 'phoromatic-storage',
			// Download Cache
			pts_client::download_cache_path() => 'download-cache',
			);
	}
	public static function restore_backup($r)
	{
		if(!isset($r[0]) || !is_file($r[0]))
		{
			echo PHP_EOL . pts_client::cli_just_bold('You must pass the name/path of the backup file to restore.') . PHP_EOL . PHP_EOL;
			return false;
		}
		$backup_archive = $r[0];
		echo pts_client::cli_just_bold('Backup File: ') . $backup_archive . PHP_EOL;
		echo pts_client::cli_just_bold('SHA1: ') . sha1_file($backup_archive) . PHP_EOL;
		if(substr($backup_archive, -4) == '.zip')
		{
			$root_restore_temp_dir = pts_client::create_temporary_directory(null, true);
			$s = pts_compression::zip_archive_extract($backup_archive, $root_restore_temp_dir);
		}
		else if(substr($backup_archive, -4) == '.tar')
		{
			$root_restore_temp_dir = dirname($backup_archive);
			$s = pts_compression::archive_extract($backup_archive);
		}
		else
		{
			echo PHP_EOL . 'Unknown file type.' . PHP_EOL;
			return false;
		}
		if(!$s)
		{
			echo PHP_EOL . 'There was a problem extracting the archive.' . PHP_EOL;
			return false;
		}
		$restore_dir = $root_restore_temp_dir . DIRECTORY_SEPARATOR . 'pts-backup' . DIRECTORY_SEPARATOR;
		if(!is_dir($restore_dir) || !is_file($restore_dir . 'pts-backup-manifest.txt'))
		{
			echo PHP_EOL . 'This does not appear to be a valid PTS backup as no pts-backup found.' . PHP_EOL;
			return false;
		}

		$manifest_files = array();
		foreach(explode(PHP_EOL, pts_file_io::file_get_contents($restore_dir . 'pts-backup-manifest.txt')) as $line)
		{
			$r = explode(': ', $line);
			$manifest_files[$r[0]] = $r[1];
		}
		// XXX decide how exactly we want to do with manifest_files

		if(is_file($restore_dir . 'phoronix-test-suite.xml'))
		{
			$restore_conf = pts_user_io::prompt_bool_input('Do you want to restore the user configuration file specifying paths, etc?', false);
			if($restore_conf)
			{
				pts_file_io::copy($restore_dir . 'phoronix-test-suite.xml', pts_config::get_config_file_location());
			}
		}

		$backup_map = self::backup_map();
		foreach($backup_map as $dest => $source)
		{
			if($source == 'phoronix-test-suite.xml')
				continue;

			if(is_dir($restore_dir . $source) || is_file($restore_dir . $source))
			{
				$s = pts_file_io::copy($restore_dir . $source, $dest);
				if($s)
					echo PHP_EOL . pts_client::cli_just_bold('Restored: ') . $source . ' to ' . $dest . PHP_EOL;
				else
					echo PHP_EOL . 'Failed to restore ' . $source . PHP_EOL;
			}
		}

		pts_file_io::delete($root_restore_temp_dir . DIRECTORY_SEPARATOR . 'pts-backup', null, true);
	}
	protected static function dir_checks($dir, $remove_base_dir, &$checksums = array())
	{
		$files = scandir($dir);

		foreach($files as $key => $value)
		{
			$p = realpath($dir . '/' . $value);
			if(!is_dir($p))
			{
				$checksums[] = str_replace($remove_base_dir, '', $p) . ': ' . sha1_file($p) . PHP_EOL;
			}
			else if($value != '.' && $value != '..')
			{
				self::dir_checks($p, $remove_base_dir, $checksums);
			}
		}

		return $checksums;
	}

}

?>
