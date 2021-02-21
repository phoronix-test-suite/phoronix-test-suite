<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012 - 2021, Phoronix Media
	Copyright (C) 2012 - 2021, Michael Larabel
	phodevi.php: The object for an effective VFS with PTS/Phodevi

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

class phodevi_vfs
{
	private $cache;
	private $options = array(
		// name => F/C - Cacheable? - File / 	Command - Additional Checks
		// F = File, C = Command
		'cpuinfo' => array('type' => 'F', 'F' => '/proc/cpuinfo', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'CPU'),
		'lscpu' => array('type' => 'C', 'C' => 'lscpu', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'CPU'),
		'lsusb' => array('type' => 'C', 'C' => 'lsusb -v', 'cacheable' => false, 'preserve' => true, 'subsystem' => 'System'),
		'sensors' => array('type' => 'C', 'C' => 'sensors', 'cacheable' => false, 'preserve' => true, 'subsystem' => 'System'),
		'cc' => array('type' => 'C', 'C' => 'cc -v', 'cacheable' => false, 'preserve' => true, 'subsystem' => 'System'),
		'scaling_available_frequencies' => array('type' => 'F', 'F' => '/sys/devices/system/cpu/cpu0/cpufreq/scaling_available_frequencies', 'cacheable' => false, 'preserve' => true, 'subsystem' => 'CPU'),
		'meminfo' => array('type' => 'F', 'F' => '/proc/meminfo', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'Memory'),
		'modules' => array('type' => 'F', 'F' => '/proc/modules', 'cacheable' => false, 'preserve' => true, 'subsystem' => 'System'),
		'cmdline' => array('type' => 'F', 'F' => '/proc/cmdline', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'System'),
		'kernel_version' => array('type' => 'F', 'F' => '/proc/version', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'System'),
		'mounts' => array('type' => 'F', 'F' => '/proc/mounts', 'cacheable' => false, 'preserve' => true, 'subsystem' => 'Disk'),
		'glxinfo' => array('type' => 'C', 'C' => 'glxinfo', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'GPU'),
		'vulkaninfo' => array('type' => 'C', 'C' => 'vulkaninfo', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'GPU'),
		'clinfo' => array('type' => 'C', 'C' => 'clinfo', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'GPU'),
		'vdpauinfo' => array('type' => 'C', 'C' => 'vdpauinfo', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'GPU'),
		'lspci' => array('type' => 'C', 'C' => 'lspci -mmkvvnn', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'System'),
		'radeon_pm_info' => array('type' => 'F', 'F' => '/sys/kernel/debug/dri/0/radeon_pm_info', 'cacheable' => false, 'preserve' => true, 'subsystem' => 'GPU'),
		'i915_capabilities' => array('type' => 'F', 'F' => '/sys/kernel/debug/dri/0/i915_capabilities', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'GPU'),
		'i915_cur_delayinfo' => array('type' => 'F', 'F' => '/sys/kernel/debug/dri/0/i915_cur_delayinfo', 'cacheable' => false, 'preserve' => true, 'subsystem' => 'GPU'),
		'i915_drpc_info' => array('type' => 'F', 'F' => '/sys/kernel/debug/dri/0/i915_drpc_info', 'cacheable' => false, 'preserve' => true, 'subsystem' => 'GPU'),
		'xorg_log' => array(
			array('type' => 'F', 'F' => '/var/log/Xorg.0.log', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'System', 'remove_timestamps' => true),
			array('type' => 'F', 'F' => '~/.local/share/xorg/Xorg.0.log', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'System', 'remove_timestamps' => true),
			array('type' => 'C', 'C' => 'journalctl -o cat /usr/bin/Xorg', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'System', 'remove_timestamps' => true),
			array('type' => 'C', 'C' => 'journalctl -o cat /usr/libexec/Xorg.bin', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'System', 'remove_timestamps' => true)
			),
		'xorg_conf' => array('type' => 'F', 'F' => '/etc/X11/xorg.conf', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'System'),
		'dmesg' => array('type' => 'C', 'C' => 'dmesg', 'cacheable' => false, 'preserve' => true, 'subsystem' => 'System', 'remove_timestamps' => true),
		);

	public function __construct()
	{
		$this->clear_cache();
	}
	public function list_cache_nodes($subsystem = null)
	{
		$nodes = array();

		if($subsystem == null)
		{
			foreach($this->options as $name => $node)
			{
				if($this->cache_isset_names($name))
				{
					array_push($nodes, $name);
				}
			}
		}
		else
		{
			$nodes = array();
			$subsystem = explode(' ', $subsystem);
			foreach($this->options as $name => $node)
			{
				if(in_array($node['subsystem'], $subsystem) && $this->cache_isset_names($name))
				{
					array_push($nodes, $name);
				}
			}
		}

		return $nodes;
	}
	public static function cleanse_file(&$file, $name = false)
	{
		switch($name)
		{
			case 'mounts':
				foreach(array('ecryptfs_cipher=', 'ecryptfs_sig=', 'ecryptfs_fnek_sig=') as $check)
				{
					if(($x = stripos($file, $check)) !== false)
					{
						$split_a = substr($file, 0, ($x + strlen($check)));

						$y = strlen($file);
						foreach(array(',', ' ', '&', PHP_EOL) as $next)
						{
							if(($z = stripos($file, $next, ($x + strlen($check)))) !== false && $z < $y)
							{
								$y = $z;
							}
						}

						$file = $split_a . 'XXXX' . substr($file, $y);
					}
				}
				break;
			default:
				$file = pts_strings::remove_lines_containing($file, array('Serial N', 'S/N', 'UUID', ' seed:', 'Serial #', 'serial:', 'serial='));
				break;
		}

		$file = strip_tags($file);
		if(function_exists('preg_replace'))
		{
			$file = preg_replace('/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i', '', $file);
		}

		return $file;
	}
	public static function cleanse_and_shorten_kernel_config($kconfig)
	{
		$kconfig = explode(PHP_EOL, $kconfig);
		foreach($kconfig as $i => &$line)
		{
			if(empty($line) || substr($line, 0, 1) == '#')
			{
				unset($kconfig[$i]);
			}
			if(isset($line[9]) && substr($line, 0, 7) == 'CONFIG_')
			{
				$line = substr($line, 7);
			}
		}

		$kconfig = implode(PHP_EOL, $kconfig);
		if(!empty($kconfig))
		{
			$kconfig = '# CONFIG_ prefix dropped, comment lines removed' . PHP_EOL . $kconfig;
		}
		return $kconfig;
	}
	public function clear_cache()
	{
		$this->cache = array();
	}
	public function cache_index()
	{
		return array_keys($this->cache);
	}
	public function __get($name)
	{
		// This assumes that isset() has been called on $name prior to actually trying to get it...

		if(isset($this->cache[$name]))
		{
			return PHP_EOL . $this->cache[$name] . PHP_EOL;
		}
		else if(PTS_IS_CLIENT && isset($this->options[$name]))
		{
			if(isset($this->options[$name]['type']))
			{
				$tries = array($this->options[$name]);
			}
			else
			{
				$tries = $this->options[$name];
			}

			$contents = null;

			foreach($tries as &$try)
			{
				if($try['type'] == 'F' && isset($try['F'][4]) && substr($try['F'], 0, 2) == '~/')
				{
					// Set the home directory
					$try['F'] = str_replace('~/', pts_core::user_home_directory(), $try['F']);
				}

				if($try['type'] == 'F' && is_file($try['F']))
				{
					if(filesize($try['F']) < 5242880)
					{
						$contents = file_get_contents($try['F']);
					}
					else
					{
						continue;
					}
				}
				else if($try['type'] == 'C')
				{
					$command = pts_client::executable_in_path(pts_strings::first_in_string($try['C']));

					if($command != null)
					{
						$descriptor_spec = array(
							0 => array('pipe', 'r'),
							1 => array('pipe', 'w'),
							2 => array('pipe', 'w')
							);
						$proc = proc_open($try['C'], $descriptor_spec, $pipes, null, null);
						$contents = stream_get_contents($pipes[1]);
						fclose($pipes[1]);
						$return_value = proc_close($proc);
					
						if(isset($contents[5242880]))
						{
							$contents = null;
						}
					}
				}

				if(isset($try['remove_timestamps']) && $try['remove_timestamps'])
				{
					// remove leading timestamps such as from dmesg and Xorg.0.log
					$contents = pts_strings::remove_line_timestamps($contents);
				}

				if($contents != null)
				{
					if($try['cacheable'])
					{
						$this->cache[$name] = $contents;
					}

					return PHP_EOL . $contents . PHP_EOL;
				}
			}
		}

		return false;
	}
	public function __isset($name)
	{
		return isset($this->cache[$name]) || (PTS_IS_CLIENT && $this->cache_isset_names($name));
	}
	public function set_cache_item($name, $cache)
	{
		$this->cache[$name] = $cache;
	}
	public function cache_isset_names($name)
	{
		// Cache the isset call names with their values when checking files/commands since Phodevi will likely hit each one potentially multiple times and little overhead to caching them
		static $isset_cache;

		if(!isset($isset_cache[$name]))
		{
			if(isset($this->options[$name]['type']))
			{
				$isset_cache[$name] = ($this->options[$name]['type'] == 'F' && is_readable($this->options[$name]['F'])) || ($this->options[$name]['type'] == 'C' && pts_client::executable_in_path(pts_strings::first_in_string($this->options[$name]['C'])));
			}
			else
			{
				$isset_cache[$name] = false;
				foreach($this->options[$name] as $try)
				{
					$isset_cache[$name] = ($try['type'] == 'F' && is_readable($try['F'])) || ($try['type'] == 'C' && pts_client::executable_in_path(pts_strings::first_in_string($try['C'])));
					if($isset_cache[$name])
					{
						break;
					}
				}
			}
		}

		return $isset_cache[$name];
	}
}

?>
