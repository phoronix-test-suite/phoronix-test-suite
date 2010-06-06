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

class pts_phoronix_stream
{
	private $position;
	private $host;
	private $path;
	private $data;
	private $data_list;
	private $type;

	public function __construct()
	{
		echo "\n__construct()\n";
		$this->position = 0;
		$this->host = null;
		$this->path = null;
		$this->data = null;
		$this->data_list = array();
	}
	public function dir_closedir()
	{
		echo "\ndir_closedir()\n";
		return true;
	}
	public function dir_opendir($path, $options)
	{
		echo "\ndir_opendir()\n";
		// check to see if $path works or not
		$url = parse_url($path);
		return false;
	}
	public function dir_readdir()
	{
		echo "\ndir_readdir()\n";
		// when not end, return filename otherwise $false
		return "TTTT";
	}
	public function dir_rewinddir()
	{
		echo "\ndir_rewinddir()\n";
		return false;
	}
	public function mkdir($path, $mode, $options)
	{
		echo "\nmkdir()\n";
		return false;
	}
	public function rename($path_from, $path_to)
	{
		echo "\nrename()\n";
		return false;
	}
	public function rmdir($path, $options)
	{
		echo "\nrmdir()\n";
		return false;
	}
	public function stream_lock($operation)
	{
		echo "\nstream_lock()\n";
		switch($operation)
		{
			case LOCK_EX:
			case LOCK_UN:
			case LOCK_NB:
			case LOCK_SH:
				break;
		}
	}
	public function stream_open($path, $mode, $options, &$opened_path)
	{
		// read whatever
		echo "\nstream_open()\n";
		$url = parse_url($path);
		print_r($url);

		$this->host = $url['host'];
		$this->path = substr($url['path'], 1);

		switch($this->host)
		{
			case "virtual":
				if($this->path == "supported-tests")
				{
					$this->data_list = array();
					$this->type = "PTS_TESTS";
					foreach(pts_tests::available_tests() as $identifier)
					{
						if((pts_is_assignment("LIST_UNSUPPORTED") xor pts_test_supported($identifier)) || pts_is_assignment("LIST_ALL_TESTS"))
						{
							array_push($this->data_list, $identifier);
						}
					}
				}
				break;
		}

		return !empty($this->data_list);
	}
	protected function parse_path($path)
	{
		$url = parse_url($path);

		if(!isset($url["path"]))
		{
			$url["path"] = $url["host"];
			$url["host"] = "main";
		}

		$this->host = $url["host"];
		$this->path = substr($url["path"], 0, 1) == '/' ? substr($url["path"], 1) : $url["path"];
		$path_r = explode('/', $this->path);

		switch($this->host)
		{
			case "virtual":
				if($this->path == "supported-tests")
				{
					$this->data = array();
					foreach(pts_tests::available_tests() as $identifier)
					{
						if((pts_is_assignment("LIST_UNSUPPORTED") xor pts_test_supported($identifier)) || pts_is_assignment("LIST_ALL_TESTS"))
						{
							array_push($this->data, $identifier);
						}
					}

				}
				break;
		}

		return !empty($this->data);
	}
	public function unlink($path)
	{
		echo "\nunlink()\n";
		return false;
	}
	public function stream_read($count)
	{
		return $this->data;
		echo "\nstream_read($count)\n";
		$read = substr($TO_READ, $this->position, $count);
		$this->position += strlen($read);

		return $read;
	}
	public function stream_write($data)
	{
		echo "\nstream_write()\n";
		return false;
	}
	public function stream_tell()
	{
		echo "\nstream_tell()\n";
		return $this->position;
	}
	public function stream_eof()
	{
		echo "\nstream_eof()\n";
		return true;
		return $this->position >= strlen($TO_READ);
	}
	public function stream_seek($offset, $whence = SEEK_SET)
	{
		echo "\nstream_seek()\n";
		return false;
	}
	public function url_stat($path, $flags)
	{
		echo "\nurl_stat()\n";

		$path_returns = 

		// files need mode 0100666
		// dirs need mode 040777

	/*	return array(
			"dev" => 1,
			"ino" => 1,
			"mode" => 040777,
			"nlink" => 1,
			"uid" => 1,
			"gid" => 1,
			"rdev" => 1,
			"size" => 1,
			"atime" => 1,
			"mtime" => 1,
			"ctime" => 1,
			"blksize" => 1,
			"blocks" => 1,
			0 => 1,
			1 => 1,
			2 => 1,
			3 => 1,
			4 => 1,
			5 => 1,
			6 => 1,
			7 => 1,
			8 => 1,
			9 => 1,
			10 => 1,
			11 => 1,
			12 => 1
		); */
	}
	
}

?>
