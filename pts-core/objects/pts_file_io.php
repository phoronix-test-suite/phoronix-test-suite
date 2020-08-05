<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2018, Phoronix Media
	Copyright (C) 2008 - 2018, Michael Larabel

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

class pts_file_io
{
	public static function mkdir($dir, $mode = 0777, $recursive = true)
	{
		// Compared to the normal PHP mkdir, don't emit a warning/notice when the directory already exists
		return !is_dir($dir) && mkdir($dir, $mode, $recursive);
	}
	public static function symlink($target, $link)
	{
		// Compared to the normal PHP symlink, don't emit a warning when the symlink already exists
		return is_file($target) && !is_file($link) ? symlink($target, $link) : false;
	}
	public static function unlink($file)
	{
		// Compared to the normal PHP mkdir, don't emit a warning/notice when the file doesn't exist
		return is_file($file) && unlink($file);
	}
	public static function glob($pattern, $flags = 0)
	{
		// Compared to the normal PHP glob, don't return false when no files are there, but return an empty array
		$r = glob($pattern, $flags);
		return is_array($r) ? $r : array();
	}
	public static function file_get_contents_if_exists($filename, $flags = 0, $context = null)
	{
		// Compared to the normal PHP file_get_contents, trim the file as a string when acquired
		return is_file($filename) ? trim(file_get_contents($filename, $flags, $context)) : false;
	}
	public static function file_get_contents($filename, $flags = 0, $context = null)
	{
		// Compared to the normal PHP file_get_contents, trim the file as a string when acquired
		return trim(file_get_contents($filename, $flags, $context));
	}
	public static function file_get_contents_first_line($filename, $flags = 0, $context = null)
	{
		// Compared to the normal PHP file_get_contents, trim the file as a string when acquired
		$f = trim(file_get_contents($filename, $flags, $context));
		return substr($f, 0, strpos($f, "\n"));
	}
	public static function delete($object, $ignore_files = null, $remove_root_directory = false)
	{
		// Delete files and/or directories
		if($object == false)
		{
			return false;
		}

		if(is_dir($object))
		{
			$object = pts_strings::add_trailing_slash($object);
		}

		foreach(pts_file_io::glob($object . '*') as $to_remove)
		{
			if(is_file($to_remove) || is_link($to_remove))
			{
				if(is_array($ignore_files) && in_array(basename($to_remove), $ignore_files))
				{
					continue; // Don't remove the file
				}
				else
				{
					unlink($to_remove);
				}
			}
			else if(is_dir($to_remove))
			{
				self::delete($to_remove, $ignore_files, true);
			}
		}

		if($remove_root_directory && is_dir($object) && count(pts_file_io::glob($object . '/*')) == 0)
		{
			rmdir($object);
		}

		return true;
	}
	public static function array_filesize($r)
	{
		$filesize = 0;

		foreach($r as $file)
		{
			if(is_file($file))
			{
				$filesize += filesize($file);
			}
		}

		return $filesize;
	}
	public static function copy($source, $dest, $no_overwrite = false)
	{
		$success = false;

		if(is_file($source))
		{
			if($no_overwrite && is_file($dest))
			{
				$success = false;
			}
			else
			{
				$success = copy($source, $dest);
			}
		}
		else if(is_link($source))
		{
			$success = copy(readlink($source), $dest);
		}
		else if(is_dir($source))
		{
			if(!is_dir($dest))
			{
				mkdir($dest);
			}

			$dir = dir($source);
			while(($entry = $dir->read()) !== false)
			{
				if($entry == '.' || $entry == '..')
				{
					continue;
				}
				self::copy($source . '/' . $entry, $dest . '/' . $entry, $no_overwrite);
			}

			$dir->close();
			$success = true;
		}

		return $success;
	}
	public static function recursively_find_files_in_directory($dir, &$found_files, $file_extension = null, $skip_directories = false)
	{
		$tree = glob(rtrim($dir, '/') . '/*');
		if(is_array($tree))
		{
			foreach($tree as $file)
			{
				if(is_dir($file))
				{
					if($skip_directories && in_array(basename($file), $skip_directories))
					{
						continue;
					}
					self::recursively_find_files_in_directory($file, $found_files, $file_extension);
				}
				else if(is_file($file) && ($file_extension == null || substr($file, 0 - strlen($file_extension)) == $file_extension))
				{
					$found_files[] = $file;
				}
			}
		}
	}
}

?>
