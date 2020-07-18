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

class pts_result_file_system
{
	protected $identifier;
	protected $hardware;
	protected $software;
	protected $json;
	protected $username;
	protected $notes;
	protected $timestamp;
	protected $client_version;
	protected $parent_result_file;

	public function __construct($identifier, $hardware, $software, $json, $username, $notes, $timestamp, $client_version, &$result_file = null)
	{
		$this->identifier = $identifier;
		$this->hardware = $hardware;
		$this->software = $software;
		$this->json = $json;
		$this->username = $username;
		$this->notes = $notes;
		$this->timestamp = $timestamp;
		$this->client_version = $client_version;
		$this->parent_result_file = &$result_file;
	}
	public function __toString()
	{
		return $this->get_identifier() . ' ' . $this->get_hardware() . ' ' . $this->get_software();
	}
	public function get_identifier()
	{
		return $this->identifier;
	}
	public function get_hardware()
	{
		return $this->hardware;
	}
	public function get_software()
	{
		return $this->software;
	}
	public function get_json()
	{
		return $this->json;
	}
	public function get_username()
	{
		return $this->username;
	}
	public function get_notes()
	{
		return $this->notes;
	}
	public function get_timestamp()
	{
		return $this->timestamp;
	}
	public function get_client_version()
	{
		return $this->client_version;
	}
	public function set_identifier($new_id)
	{
		$this->identifier = $new_id;
	}
	public function get_cpu_core_count()
	{
		$hw = $this->get_hardware();
		$hw = strstr($hw, 'Processor:');
		$hw = strstr($hw, ',', true);
		$hw = substr(strstr($hw, '('), 1);

		if(($x = strpos($hw, ' Cores')) !== false)
		{
			$hw = substr($hw, 0, $x);
		}

		return is_numeric($hw) ? $hw : false;
	}
	public function get_cpu_thread_count()
	{
		$hw = $this->get_hardware();
		$hw = strstr($hw, 'Processor:');
		$hw = strstr($hw, ',', true);
		$hw = substr(strstr($hw, '('), 1);

		if(($x = strpos($hw, ' Threads')) !== false)
		{
			$hw = substr($hw, 0, $x);
			if(($x = strpos($hw, ' / ')) !== false)
			{
				$hw = substr($hw, $x + 3);
			}
		}

		return is_numeric($hw) && $hw > 0 ? $hw : $this->get_cpu_core_count();
	}
	public function get_cpu_clock()
	{
		$hw = $this->get_hardware();
		$hw = strstr($hw, 'Processor:');
		$hw = strstr($hw, ',', true);
		$hw = strstr($hw, '(', true);

		if(($x = strpos($hw, ' @ ')) !== false)
		{
			$hw = substr($hw, $x + 3);
			if(($x = strpos($hw, 'GHz')) !== false)
			{
				$hw = substr($hw, 0, $x);
			}
		}

		return is_numeric($hw) ? $hw : false;
	}
	public function get_memory_channels()
	{
		$memory_channels = -1;
		$dimm_count = $this->get_memory_dimm_count();
		$socket_count = $this->get_cpu_socket_count();
		if($dimm_count > 0 && $dimm_count > $socket_count)
		{
			$memory_channels = $dimm_count / $socket_count;
		}

		return $memory_channels > 0 && is_int($memory_channels) ? $memory_channels : -1;
	}
	public function get_memory_dimm_count()
	{
		$hw = $this->get_hardware();
		$hw = substr(strstr($hw, 'Memory:'), 8);
		$hw = strstr($hw, ',', true);

		if(($x = strpos($hw, ' x ')) !== false)
		{
			$hw = substr($hw, 0, $x);
		}
		else
		{
			$hw = -1;
		}

		return is_numeric($hw) ? $hw : -1;
	}
	public function get_cpu_socket_count()
	{
		$hw = $this->get_hardware();
		$hw = substr(strstr($hw, 'Processor:'), 11);
		$hw = strstr($hw, ',', true);

		if(($x = strpos($hw, ' x ')) !== false)
		{
			$hw = substr($hw, 0, $x);
		}
		else
		{
			$hw = 1;
		}

		return is_numeric($hw) && $hw > 0 ? $hw : 1;
	}
	public function log_files($read_file = false)
	{
		$files = array();
		if($this->parent_result_file)
		{
			if(($d = $this->parent_result_file->get_system_log_dir($this->get_identifier(), true)))
			{
				foreach(pts_file_io::glob($d . '/*') as $file)
				{
					$basename_file = basename($file);
					if($read_file !== false && $basename_file == $read_file)
					{
						return phodevi_vfs::cleanse_file(file_get_contents($file), $basename_file);
					}
					$files[] = $basename_file;
				}
			}
			else if($this->parent_result_file->get_result_dir() && is_file($this->parent_result_file->get_result_dir() . 'system-logs.zip') && extension_loaded('zip'))
			{
				$zip = new ZipArchive();
				$res = $zip->open($this->parent_result_file->get_result_dir() . 'system-logs.zip');

				if($res === true)
				{
					$log_path = 'system-logs/' . $this->get_identifier() . '/';
					$log_path_l = strlen($log_path);
					for($i = 0; $i < $zip->numFiles; $i++)
					{
						$index = $zip->getNameIndex($i);
						if(substr($index, 0, $log_path_l) == $log_path)
						{
							$basename_file = substr($index, $log_path_l);

							if($basename_file != null)
							{
								if($read_file !== false && $basename_file == $read_file)
								{
									$c = $zip->getFromName($index);
									$contents = phodevi_vfs::cleanse_file($c, $basename_file);
									$zip->close();
									return $contents;
								}
								array_push($files, $basename_file);
							}

						}
					}
					$zip->close();
				}
			}
		}

		return $read_file !== false ? false : $files;
	}
}

?>
