<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel
	phodevi_cache.php: The phodevi_cache object for storing the device cache

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

class phodevi_cache
{
	private $phodevi_cache;
	private $phodevi_cs;
	private $phodevi_time;
	private $storage_dir;
	private $client_version;

	public function __construct($phodevi_cache, $storage_dir = null, $client_version = null)
	{
		$this->phodevi_cache = $phodevi_cache;
		$this->phodevi_cs = md5(serialize($phodevi_cache)); // Checksum
		$this->phodevi_time = time();
		$this->storage_dir = $storage_dir;
		$this->client_version = $client_version;
	}
	/**
	 * @param string $storage_dir
	 * @param int    $client_version
	 *
	 * @return array[]
	 */
	public function restore_cache($storage_dir = null, $client_version = null)
	{
		$restore_cache = null;

		if(($this->storage_dir == $storage_dir || $storage_dir == null) && $this->client_version == $client_version)
		{
			if($this->phodevi_time > (time() - phodevi::system_uptime()))
			{
				if(md5(serialize($this->phodevi_cache)) == $this->phodevi_cs)
				{
					$restore_cache = $this->phodevi_cache;
				}
			}
		}

		return $restore_cache;
	}
	public function read_cache()
	{
		return $this->phodevi_cache;
	}
}

?>
