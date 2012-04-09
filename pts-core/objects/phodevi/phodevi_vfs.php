<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2012, Phoronix Media
	Copyright (C) 2012, Michael Larabel
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
		// name => F/C - Cacheable? - File / Command - Additional Checks
		// F = File, C = Command
		'cpuinfo' => array('type' => 'F', 'F' => '/proc/cpuinfo', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'CPU'),
		'glxinfo' => array('type' => 'C', 'C' => 'glxinfo', 'cacheable' => true, 'preserve' => true, 'subsystem' => 'GPU'),
		);

	public function __construct()
	{
		$this->cache = array();
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
			if($this->options[$name]['type'] == 'F')
			{
				$contents = file_get_contents($this->options[$name]['F']);
			}
			else if($this->options[$name]['type'] == 'C')
			{
				$command = pts_client::executable_in_path(pts_strings::first_in_string($this->options[$name]['C']));
				$descriptor_spec = array(
					0 => array('pipe', 'r'),
					1 => array('pipe', 'w'),
					2 => array('pipe', 'w')
					);
				$proc = proc_open($command, $descriptor_spec, $pipes, null, null);
				$contents = stream_get_contents($pipes[1]);
				fclose($pipes[1]);
				$return_value = proc_close($proc);
			}

			if($this->options[$name]['cacheable'])
			{
				$this->cache[$name] = $contents;
			}

			return PHP_EOL . $contents . PHP_EOL;
		}

		return false;
	}
	public function __isset($name)
	{
		return isset($this->cache[$name]) || (PTS_IS_CLIENT && $this->cache_isset_names($name));
	}
	protected function cache_isset_names($name)
	{
		// Cache the isset call names with their values when checking files/commands since Phodevi will likely hit each one potentially multiple times and little overhead to caching them
		static $isset_cache;

		if(!isset($isset_cache[$name]))
		{
			$isset_cache[$name] = ($this->options[$name]['type'] == 'F' && is_readable($this->options[$name]['F'])) || ($this->options[$name]['type'] == 'C' && pts_client::executable_in_path(pts_strings::first_in_string($this->options[$name]['C'])));
		}

		return $isset_cache[$name];
	}
}

?>
