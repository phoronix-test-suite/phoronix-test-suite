<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2010, Phoronix Media
	Copyright (C) 2008 - 2010, Michael Larabel

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
	public static function mkdir($dir, $mode = 0777, $recursive = false)
	{
		// Compared to the normal PHP mkdir, don't emit a warning/notice when the directory already exists
		return !is_dir($dir) && mkdir($dir, $mode, $recursive);
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
}

?>
