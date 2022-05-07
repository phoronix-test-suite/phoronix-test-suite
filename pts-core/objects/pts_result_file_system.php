<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015 - 2021, Phoronix Media
	Copyright (C) 2015 - 2021, Michael Larabel

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
	protected $has_log_files = -1;
	protected $original_identifier;

	public function __construct($identifier, $hardware, $software, $json, $username, $notes, $timestamp, $client_version, &$result_file = null)
	{
		$this->identifier = $identifier;
		$this->original_identifier = $identifier; // track if the run was later renamed (i.e. dynamically on page load)
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
	public function get_original_identifier()
	{
		return $this->original_identifier;
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
	public function has_log_files()
	{
		if($this->has_log_files == -1)
		{
			$this->has_log_files = count($this->log_files()) > 0;
		}

		return $this->has_log_files;
	}
	protected function recursive_glob_dir($dir, $root_dir, &$files, $read_file = false, $cleanse_file = true)
	{
		if(empty($dir))
		{
			return;
		}

		foreach(pts_file_io::glob($dir . '/*') as $file)
		{
			if(is_file($file))
			{
				$basename_file = substr($file, strlen($root_dir) + 1);
				if($read_file !== false && $basename_file == $read_file)
				{
					$file = file_get_contents($file);
					return $cleanse_file ? phodevi_vfs::cleanse_file($file, $basename_file) : $file;
				}
				$files[] = $basename_file;
			}
			else if(is_dir($file))
			{
				$ret = $this->recursive_glob_dir($file, $root_dir, $files, $read_file, $cleanse_file);
				if(!empty($ret))
				{
					return $ret;
				}
			}
		}
	}
	public function log_files($read_file = false, $cleanse_file = true)
	{
		$files = array();
		if($this->parent_result_file)
		{
			if(($d = $this->parent_result_file->get_system_log_dir($this->get_identifier(), true)) || (($this->get_identifier() != $this->get_original_identifier() && ($d = $this->parent_result_file->get_system_log_dir($this->get_original_identifier(), true)))))
			{
				$ret = $this->recursive_glob_dir($d, $d, $files, $read_file, $cleanse_file);
				if(!empty($ret))
				{
					return $ret;
				}
			}
			else if($this->parent_result_file->get_result_dir() && is_file($this->parent_result_file->get_result_dir() . 'system-logs.zip') && extension_loaded('zip'))
			{
				$zip = new ZipArchive();
				$res = $zip->open($this->parent_result_file->get_result_dir() . 'system-logs.zip');

				if($res === true)
				{
					$possible_log_paths = array('system-logs/' . $this->get_identifier() . '/');
					if($this->get_identifier() != ($simplified = pts_strings::simplify_string_for_file_handling($this->get_identifier())))
					{
						$possible_log_paths[] = 'system-logs/' . $simplified . '/';
					}

					if($this->get_identifier() != $this->get_original_identifier())
					{
						// If the identifier was dynamically renamed, check back to see if the archived zip data is of the old name
						// i.e. when dynamically renaming a run just on the web page for a given page load but not altering the archived data
						$possible_log_paths[] = 'system-logs/' . $this->get_original_identifier() . '/';
					}

					foreach($possible_log_paths as $log_path)
					{
						$log_path_l = strlen($log_path);
						for($i = 0; $i < $zip->numFiles; $i++)
						{
							$index = $zip->getNameIndex($i);
							if(isset($index[$log_path_l]) && substr($index, 0, $log_path_l) == $log_path)
							{
								$basename_file = substr($index, $log_path_l);

								if($basename_file != null)
								{
									if($read_file !== false && $basename_file == $read_file)
									{
										$c = $zip->getFromName($index);
										$contents = $cleanse_file ? phodevi_vfs::cleanse_file($c, $basename_file) : $c;
										$zip->close();
										return $contents;
									}
									$files[] = $basename_file;
								}
							}
						}

						if(!empty($files))
						{
							// If files found, no use iterating to check any original identifier
							break;
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
