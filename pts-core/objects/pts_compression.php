<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2017, Phoronix Media
	Copyright (C) 2008 - 2017, Michael Larabel

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

class pts_compression
{
	public static function compress_to_archive($to_compress, $compress_to)
	{
		$compress_to_file = basename($compress_to);
		$compress_base_dir = dirname($to_compress);
		$compress_base_name = basename($to_compress);

		switch(substr($compress_to_file, strpos($compress_to_file, '.') + 1))
		{
			case 'tar':
				$extract_cmd = 'tar -cf ' . $compress_to . ' ' . $compress_base_name;
				break;
			case 'tar.gz':
				$extract_cmd = 'tar -czf ' . $compress_to . ' ' . $compress_base_name;
				break;
			case 'tar.bz2':
				$extract_cmd = 'tar -cjf ' . $compress_to . ' ' . $compress_base_name;
				break;
			case 'zip':
				$extract_cmd = 'zip -r ' . $compress_to . ' ' . $compress_base_name;
				break;
			default:
				$extract_cmd = null;
				break;
		}

		if($extract_cmd != null)
		{
			shell_exec('cd ' . $compress_base_dir . ' && ' . $extract_cmd . ' 2>&1');
		}
	}
	public static function archive_extract($file)
	{
		$file_name = basename($file);
		$file_path = dirname($file);

		switch(substr($file_name, strpos($file_name, '.') + 1))
		{
			case 'tar':
				$extract_cmd = 'tar -xf';
				break;
			case 'tar.gz':
				$extract_cmd = 'tar -zxf';
				break;
			case 'tar.bz2':
				$extract_cmd = 'tar -jxf';
				break;
			case 'tar.xz':
				$extract_cmd = 'tar xf';
				break;
			case 'zip':
				$extract_cmd = 'unzip -o';
				break;
			default:
				return false;
		}

		shell_exec('cd ' . $file_path . ' && ' . $extract_cmd . ' ' . $file_name . ' 2>&1');
		return true;
	}
	public static function zip_archive_extract($zip_file, $extract_to)
	{
		$success = false;
		if(!is_readable($zip_file) || !is_writable($extract_to))
		{
			return $success;
		}

		if(class_exists('ZipArchive'))
		{
			$zip = new ZipArchive();

			if($zip->open($zip_file) === true)
			{
				$t = $zip->extractTo($extract_to);
				$zip->close();
				$success = $t;
			}
		}
		else if(function_exists('zip_open'))
		{
			// the old PHP Zip API, but this is what webOS Optware uses and others
			$zip = zip_open($zip_file);

			if($zip)
			{
				while($zip_entry = zip_read($zip))
				{
					$fp = fopen($extract_to . '/' . zip_entry_name($zip_entry), 'w');

					if(zip_entry_open($zip, $zip_entry, 'r'))
					{
						$buf = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
						fwrite($fp, $buf);
						zip_entry_close($zip_entry);
						fclose($fp);
					}
				}
				zip_close($zip);
				$success = true;
			}
		}
		else if(pts_client::executable_in_path('unzip'))
		{
			// Fallback to using external unzip command
			shell_exec('unzip -o ' . $zip_file . ' -d ' . $extract_to . ' 2>&1');
			$success = true;
		}
		else if(PTS_IS_CLIENT)
		{
			trigger_error('Failed to find ZIP support for extracting file: ' . $zip_file . '. Install PHP ZIP support or the unzip utility.', E_USER_ERROR);
		}

		return $success;
	}
	public static function zip_archive_create($zip_file, $add_files)
	{
		if(!class_exists('ZipArchive'))
		{
			if(pts_client::executable_in_path('zip'))
			{
				if(is_array($add_files))
				{
					shell_exec('cd ' . dirname($add_files[0]) . ' && rm -f ' . $zip_file . ' && zip -r ' . $zip_file . ' ' . implode(' ', array_map('basename', $add_files)));
				}
				else
				{
					shell_exec('cd ' . dirname($add_files) . ' && rm -f ' . $zip_file . ' && zip -r ' . $zip_file . ' ' . basename($add_files));
				}

				if(is_file($zip_file) && filesize($zip_file) > 0)
				{
					return true;
				}
			}

			return false;
		}

		$zip = new ZipArchive();

		// Avoid "using empty file as ZipArchive is deprecated"
		if(is_file($zip_file))
		{
			// Avoid PHP8 warning on line below like when empty file made by tempnam
			unlink($zip_file);
		}
		if($zip->open($zip_file, ZIPARCHIVE::CREATE) !== true)
		{
			$success = false;
		}
		else
		{
			foreach(pts_arrays::to_array($add_files) as $add_file)
			{
				self::zip_archive_add($zip, $add_file, dirname($add_file));
			}

			$success = true;
		}

		return $success;
	}
	protected static function zip_archive_add(&$zip, $add_file, $base_dir = null)
	{
		if(PTS_IS_CLIENT && phodevi::is_windows())
		{
			$add_file = str_replace('/\\', '/', $add_file);
			$add_file = str_replace('//', '/', $add_file);
			$base_dir = str_replace('/\\', '/', $base_dir);
		}
		if(is_dir($add_file))
		{
			$zip->addEmptyDir(substr($add_file, strlen(pts_strings::add_trailing_slash($base_dir))));

			if(PTS_IS_CLIENT && phodevi::is_windows())
			{
				$tadd = pts_file_io::glob($add_file . '/*');
			}
			else
			{
				$tadd = pts_file_io::glob(pts_strings::add_trailing_slash($add_file) . '*');
			}
			foreach($tadd as $new_file)
			{
				self::zip_archive_add($zip, $new_file, $base_dir);
			}
		}
		else if(is_file($add_file))
		{
			$zip->addFile($add_file, substr($add_file, strlen(pts_strings::add_trailing_slash($base_dir))));
		}
	}
	public static function zip_archive_read_all_files($zip_file)
	{
		if(!class_exists('ZipArchive') || !is_readable($zip_file))
		{
			return false;
		}

		$zip = new ZipArchive();

		if($zip->open($zip_file) === true)
		{
			$files = array();

			for($i = 0; $i < $zip->numFiles; $i++)
			{
				$filename = $zip->getNameIndex($i);
				$files[$filename] = $zip->getFromName($filename);
			}

			$zip->close();
		}
		else
		{
			$files = false;
		}

		return $files;
	}
}

?>
