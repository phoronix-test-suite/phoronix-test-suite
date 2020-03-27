<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2019, Phoronix Media
	Copyright (C) 2008 - 2019, Michael Larabel

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
	static $longest_file_name = null;
	static $longest_file_name_length = 0;

	private $url;
	private $filename;
	private $filesize;
	private $md5;
	private $sha256;
	private $architecture;
	private $platform;
	private $is_optional = false;

	private $download_location_type = null;
	private $download_location_path = null;

	/**
	 * @param string|null $url
	 * @param string|null $filename
	 * @param int         $filesize
	 * @param string|null $md5
	 * @param string|null $sha256
	 * @param string|null $platform
	 * @param string|null $architecture
	 * @param string|null $is_optional
	 */
	public function __construct($url = null, $filename = null, $filesize = 0, $md5 = null, $sha256 = null, $platform = null, $architecture = null, $is_optional = false)
	{
		$this->filename = empty($filename) ? basename($url) : $filename;
		$this->url = $this->filename == $url ? null : $url;
		$this->filesize = !is_numeric($filesize) ? 0 : $filesize;
		$this->md5 = $md5;
		$this->sha256 = $sha256;
		$this->location_type = null;
		$this->location_path = array();
		$this->platform = $platform;
		$this->architecture = $architecture;
		$this->is_optional = $is_optional || strtolower($is_optional) == 'true';

		if(phodevi::is_windows() || !extension_loaded('openssl'))
		{
			// Windows with PHP stock binaries has problems downloading from HTTPS
			$this->url = str_replace('https://', 'http://', $this->url);
		}

		// Check for longest file name length as the text UI takes advantage of it

		if(strlen($this->filename) > self::$longest_file_name_length)
		{
			self::$longest_file_name = $this->filename;
			self::$longest_file_name_length = strlen($this->filename);
		}
	}
	public function get_download_url_array()
	{
		return pts_strings::comma_explode($this->url);
	}
	public function get_download_url_string()
	{
		return $this->url;
	}
	/**
	 * @return string[]
	 */
	public function get_platform_array()
	{
		return pts_strings::comma_explode($this->platform);
	}
	public function get_platform_string()
	{
		return $this->platform;
	}
	/**
	 * @return string[]
	 */
	public function get_architecture_array()
	{
		return pts_strings::comma_explode($this->architecture);
	}
	public function get_architecture_string()
	{
		return $this->architecture;
	}
	public function get_filename()
	{
		return $this->filename;
	}
	/**
	 * @return string
	 */
	public function get_filesize()
	{
		return $this->filesize;
	}
	public function get_md5()
	{
		return $this->md5;
	}
	public function get_sha256()
	{
		return $this->sha256;
	}
	public function is_optional()
	{
		return $this->is_optional == true;
	}
	public function is_optional_string()
	{
		return $this->is_optional ? 'TRUE' : '';
	}
	public function check_file_hash($file)
	{
		if(!is_file($file))
		{
			return false;
		}
		else if(pts_client::read_env('NO_FILE_HASH_CHECKS') != false || pts_client::read_env('NO_MD5_CHECKS') != false)
		{
			return true;
		}
		else if($this->sha256 && function_exists('hash_file'))
		{
			return hash_file('sha256', $file) == $this->sha256;
		}
		else if($this->md5)
		{
			return md5_file($file) == $this->md5;
		}
		else if(filesize($file) > 0)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	public function set_filesize($size)
	{
		$this->filesize = is_numeric($size) ? $size : 0;
	}
	public function set_download_location($location_type, $location_path = array())
	{
		// IN_DESTINATION_DIR == already good, in the destination directory already, was previously downloaded
		// LOCAL_DOWNLOAD_CACHE == In a local download cache, can be copied, etc
		// REMOTE_DOWNLOAD_CACHE == In a remote download cache for download
		// LOOKASIDE_DOWNLOAD_CACHE == In another test installation directory

		$this->download_location_type = $location_type;
		$this->download_location_path = $location_path;
	}
	public function get_download_location_type()
	{
		return $this->download_location_type;
	}
	public function get_download_location_path()
	{
		return $this->download_location_path;
	}
}

?>
