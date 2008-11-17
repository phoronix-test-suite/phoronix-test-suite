<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel

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

class pts_test_file_download
{
	var $url;
	var $filename;
	var $filesize;
	var $md5;

	public function __construct($url = null, $filename = null, $filesize = 0, $md5 = null)
	{
		if(empty($filename))
		{
			$filename = basename($url);
		}
		if($filename == $url)
		{
			$url = "";
		}	
		if(!is_numeric($filesize))
		{
			$filesize = 0;
		}

		$this->url = $url;
		$this->filename = $filename;
		$this->filesize = $filesize;
		$this->md5 = $md5;
	}
	public function get_download_url_array()
	{
		return array_map("trim", explode(",", $this->url));
	}
	public function get_filename()
	{
		return $this->filename;
	}
	public function get_filesize()
	{
		return $this->filesize;
	}
	public function get_md5()
	{
		return $this->md5;
	}
}

?>
