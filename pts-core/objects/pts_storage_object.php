<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel
	pts_storage_object.php: An object for storing other PTS objects on the disk

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

class pts_storage_object
{
	private $object_cache;
	private $object_cs;
	private $creation_time;
	private $span_reboots;
	private $span_versions;
	private $pts_version;

	public function __construct($span_reboots = true, $span_versions = true)
	{
		$this->creation_time = time();
		$this->span_reboots = $span_reboots;
		$this->span_versions = $span_versions;
		$this->pts_version = PTS_CORE_VERSION;
		$this->object_cache = array();
	}
	public function add_object($identifier, $object)
	{
		$this->object_cache[$identifier] = $object;
	}
	public function read_object($identifier)
	{
		return isset($this->object_cache[$identifier]) ? $this->object_cache[$identifier] : false;
	}
	public function remove_object($identifier)
	{
		unset($this->object_cache[$identifier]);
	}
	public function get_objects()
	{
		return $this->object_cache;
	}
	public function save_to_file($destination)
	{
		$this->object_cs = md5(serialize($this->get_objects())); // Checksum
		$string_version = base64_encode(serialize($this));
		file_put_contents($destination, wordwrap($string_version, 80, PHP_EOL, true));
		chmod($destination, 0600);
	}
	public function get_pts_version()
	{
		return $this->pts_version;
	}
	public function get_object_checksum()
	{
		return $this->object_cs;
	}
	public function get_span_reboots()
	{
		return $this->span_reboots;
	}
	public function get_span_versions()
	{
		return $this->span_versions !== false;
	}
	public function get_creation_time()
	{
		return $this->creation_time;
	}
	public static function recover_from_file($read_from_file)
	{
		$restore_obj = false;

		if(is_file($read_from_file))
		{
			$restore = unserialize(base64_decode(file_get_contents($read_from_file)));

			if($restore instanceof pts_storage_object)
			{
				if(($restore->get_span_versions() || $restore->get_pts_version() == PTS_CORE_VERSION) && md5(serialize($restore->get_objects())) == $restore->get_object_checksum())
				{
					if($restore->get_span_reboots() == false)
					{
						$continue_loading = $restore->get_creation_time() > (time() - phodevi::system_uptime());
					}
					else
					{
						$continue_loading = true;
					}

					if($continue_loading)
					{
						$restore_obj = $restore;
					}
				}
			}
		}

		return $restore_obj;
	}
	public static function set_in_file($storage_file, $identifier, $object)
	{
		$storage = self::recover_from_file($storage_file);

		if($storage != false)
		{
			$storage->add_object($identifier, $object);
			$storage->save_to_file($storage_file);
		}
	}
	public static function remove_in_file($storage_file, $identifier)
	{
		$storage = self::recover_from_file($storage_file);

		if($storage != false)
		{
			$storage->remove_object($identifier);
			$storage->save_to_file($storage_file);
		}
	}
	public static function add_in_file($storage_file, $identifier, $add)
	{
		$storage = self::recover_from_file($storage_file);

		if($storage != false)
		{
			$current_value = $storage->read_object($identifier);
			$current_value = is_numeric($current_value) ? $current_value : 0;

			$storage->add_object($identifier, $current_value + $add);
			$storage->save_to_file($storage_file);
		}
	}
	public static function read_from_file($storage_file, $identifier)
	{
		$storage = self::recover_from_file($storage_file);
		$object = false;

		if($storage != false)
		{
			$object = $storage->read_object($identifier);
		}

		return $object;
	}
}

?>
