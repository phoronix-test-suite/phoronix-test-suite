<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	phodevi_disk.php: The PTS Device Interface object for the system disk(s)

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

class phodevi_disk extends pts_device_interface
{
	public static function read_sensor($identifier)
	{
		switch($identifier)
		{
			case "temperature":
				$sensor = "hdd_temperature";
				break;
			case "read-speed":
				$sensor = "hdd_read_speed";
				break;
			case "write-speed":
				$sensor = "hdd_write_speed";
				break;
			default:
				$sensor = false;
				break;
		}

		return $sensor;
	}
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case "identifier":
				$property = new pts_device_property("hdd_string", PHODEVI_SMART_CACHE);
				break;
			case "scheduler":
				$property = new pts_device_property("hdd_scheduler", PHODEVI_SMART_CACHE);
				break;
		}

		return $property;
	}
	public static function device_notes()
	{
		$notes = array();

		if(($disk_scheduler = phodevi::read_property("disk", "scheduler")) != null)
		{
			array_push($notes, "Disk Scheduler: " . $disk_scheduler);
		}

		return $notes;
	}
	public static function hdd_string()
	{
		$disks = array();

		if(IS_MACOSX)
		{
			// TODO: Support reading non-SATA drives and more than one drive
			$capacity = phodevi_osx_parser::read_osx_system_profiler("SPSerialATADataType", "Capacity");
			$model = phodevi_osx_parser::read_osx_system_profiler("SPSerialATADataType", "Model");

			if(($cut = strpos($capacity, " (")) !== false)
			{
				$capacity = substr($capacity, 0, $cut);
			}

			if(!empty($capacity) && !empty($model))
			{
				$disks = array($capacity . " " . $model);
			}
		}
		else if(IS_BSD)
		{
			$i = 0;

			do
			{
				$disk = phodevi_bsd_parser::read_sysctl("dev.ad." . $i . ".%desc");

				if($disk != false)
				{
					array_push($disks, $disk);
				}
				$i++;
			}
			while(($disk != false || $i < 9) && $i < 128);
			// On some systems, the first drive seems to be at dev.ad.8 rather than starting at dev.ad.0
		}
		else if(IS_LINUX)
		{
			$disks_formatted = array();
			$disks = array();

			foreach(pts_glob("/sys/block/sd*") as $sdx)
			{
				if(is_file($sdx . "/device/model") && is_file($sdx . "/size"))
				{
					$disk_size = pts_file_get_contents($sdx . "/size");
					$disk_model = pts_file_get_contents($sdx . "/device/model");

					$disk_size = round($disk_size * 512 / 1000000000) . "GB";

					if(strpos($disk_model, $disk_size . " ") === false && strpos($disk_model, " " . $disk_size) === false && $disk_size != "1GB")
					{
						$disk_model = $disk_size . " " . $disk_model;
					}

					if($disk_size > 0)
					{
						array_push($disks_formatted, $disk_model);
					}
				}
			}

			for($i = 0; $i < count($disks_formatted); $i++)
			{
				if(!empty($disks_formatted[$i]))
				{
					$times_found = 1;

					for($j = ($i + 1); $j < count($disks_formatted); $j++)
					{
						if($disks_formatted[$i] == $disks_formatted[$j])
						{
							$times_found++;
							$disks_formatted[$j] = "";
						}
					}

					$disk = ($times_found > 1 ? $times_found . " x "  : "") . $disks_formatted[$i];
					array_push($disks, $disk);
				}
			}
		}

		if(count($disks) == 0)
		{
			$root_disk_size = ceil(disk_total_space("/") / 1073741824);
			$pts_disk_size = ceil(disk_total_space(TEST_ENV_DIR) / 1073741824);

			if($pts_disk_size > $root_disk_size)
			{
				$root_disk_size = $pts_disk_size;
			}

			if($root_disk_size > 1)
			{
				$disks = $root_disk_size . "GB";
			}
			else
			{
				$disks = "Unknown";
			}
		}
		else
		{
			$disks = implode(" + ", $disks);
		}

		return $disks;
	}
	public static function hdd_scheduler()
	{
		$scheduler = null;

		if(is_readable("/sys/block/sda/queue/scheduler"))
		{
			$scheduler = pts_file_get_contents("/sys/block/sda/queue/scheduler");

			if(($s = strpos($scheduler, "[")) !== false && ($e = strpos($scheduler, "]", $s)) !== false)
			{
				$scheduler = strtoupper(substr($scheduler, $s + 1, $e - $s - 1));
			}
		}

		return $scheduler;
	}
	public static function hdd_temperature($disk = null)
	{
		// Attempt to read temperature using hddtemp
		return phodevi_parser::read_hddtemp($disk);
	}
	public static function hdd_read_speed()
	{
		// speed in MB/s
		$speed = -1;

		if(IS_LINUX)
		{
			static $sys_disk = null;

			if($sys_disk == null)
			{
				foreach(pts_glob("/sys/class/block/sd*/stat") as $check_disk)
				{
					if(pts_file_get_contents($check_disk) != null)
					{
						$sys_disk = $check_disk;
						break;
					}
				}
			}

			$speed = phodevi_linux_parser::read_sys_disk_speed($sys_disk, "READ");
		}

		return $speed;
	}
	public static function hdd_write_speed()
	{
		// speed in MB/s
		$speed = -1;

		if(IS_LINUX)
		{
			static $sys_disk = null;

			if($sys_disk == null)
			{
				foreach(pts_glob("/sys/class/block/sd*/stat") as $check_disk)
				{
					if(pts_file_get_contents($check_disk) != null)
					{
						$sys_disk = $check_disk;
						break;
					}
				}
			}

			$speed = phodevi_linux_parser::read_sys_disk_speed($sys_disk, "WRITE");
		}

		return $speed;
	}
}

?>
