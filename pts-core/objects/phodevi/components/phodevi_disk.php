<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel
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

class phodevi_disk extends phodevi_device_interface
{
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case 'identifier':
				$property = new phodevi_device_property('hdd_string', phodevi::smart_caching);
				break;
			case 'scheduler':
				$property = new phodevi_device_property('hdd_scheduler', phodevi::smart_caching);
				break;
		}

		return $property;
	}
	public static function device_notes()
	{
		$notes = array();

		if(($disk_scheduler = phodevi::read_property('disk', 'scheduler')) != null)
		{
			array_push($notes, 'Disk Scheduler: ' . $disk_scheduler);
		}

		return $notes;
	}
	public static function is_genuine($disk)
	{
		return strpos($disk, ' ') > 1 && strlen($disk) == strlen(str_ireplace(array('VBOX', 'QEMU', 'Virtual'), null, $disk));
		// pts_strings::string_contains($mobo, pts_strings::CHAR_NUMERIC);
	}
	public static function hdd_string()
	{
		$disks = array();

		if(phodevi::is_macosx())
		{
			// TODO: Support reading non-SATA drives and more than one drive
			$capacity = phodevi_osx_parser::read_osx_system_profiler('SPSerialATADataType', 'Capacity');
			$model = phodevi_osx_parser::read_osx_system_profiler('SPSerialATADataType', 'Model');

			if(($cut = strpos($capacity, ' (')) !== false)
			{
				$capacity = substr($capacity, 0, $cut);
			}

			if(($cut = strpos($capacity, ' ')) !== false)
			{
				if(is_numeric(substr($capacity, 0, $cut)))
				{
					$capacity = floor(substr($capacity, 0, $cut)) . substr($capacity, $cut);
				}
			}

			$capacity = str_replace(' GB', 'GB', $capacity);

			if(!empty($capacity) && !empty($model))
			{
				$disks = array($capacity . ' ' . $model);
			}
		}
		else if(phodevi::is_bsd())
		{
			$i = 0;

			do
			{
				$disk = phodevi_bsd_parser::read_sysctl('dev.ad.' . $i . '.%desc');

				if($disk != false)
				{
					array_push($disks, $disk);
				}
				$i++;
			}
			while(($disk != false || $i < 9) && $i < 128);
			// On some systems, the first drive seems to be at dev.ad.8 rather than starting at dev.ad.0
		}
		else if(phodevi::is_solaris())
		{
			if(is_executable('/usr/ddu/bin/i386/hd_detect'))
			{
				$hd_detect = explode(PHP_EOL, trim(shell_exec('/usr/ddu/bin/i386/hd_detect -l 2>&1')));

				foreach($hd_detect as $hd_line)
				{
					if(isset($hd_line) && ($hd_pos = strpos($hd_line, ':/')) != false)
					{
						$disk = trim(substr($hd_line, 0, $hd_pos));
						$disk = self::prepend_disk_vendor($disk);

						if($disk != 'blkdev')
						{
							array_push($disks, $disk);
						}
					}
				}
			}

		}
		else if(phodevi::is_linux())
		{
			$disks_formatted = array();
			$disks = array();

			foreach(pts_file_io::glob('/sys/block/sd*') as $sdx)
			{
				if(is_file($sdx . '/device/model') && is_file($sdx . '/size'))
				{
					$disk_size = pts_file_io::file_get_contents($sdx . '/size');
					$disk_model = pts_file_io::file_get_contents($sdx . '/device/model');
					$disk_removable = pts_file_io::file_get_contents($sdx . '/removable');

					if($disk_removable == '1')
					{
						// Don't count removable disks
						continue;
					}

					$disk_size = round($disk_size * 512 / 1000000000) . 'GB';
					$disk_model = self::prepend_disk_vendor($disk_model);

					if(strpos($disk_model, $disk_size . ' ') === false && strpos($disk_model, ' ' . $disk_size) === false && $disk_size != '1GB')
					{
						$disk_model = $disk_size . ' ' . $disk_model;
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
							$disks_formatted[$j] = '';
						}
					}

					$disk = ($times_found > 1 ? $times_found . ' x '  : null) . $disks_formatted[$i];
					array_push($disks, $disk);
				}
			}
		}

		if(is_file('/sys/class/block/mmcblk0/device/name'))
		{
			$disk_name = pts_file_io::file_get_contents('/sys/class/block/mmcblk0/device/name');
			$disk_size = pts_file_io::file_get_contents('/sys/class/block/mmcblk0/size');
			array_push($disks, round($disk_size * 512 / 1000000000) . 'GB ' . $disk_name);
		}

		if(count($disks) == 0)
		{
			$root_disk_size = ceil(disk_total_space('/') / 1073741824);
			$pts_disk_size = ceil(disk_total_space(pts_client::test_install_root_path()) / 1073741824);

			if($pts_disk_size > $root_disk_size)
			{
				$root_disk_size = $pts_disk_size;
			}

			if($root_disk_size > 1)
			{
				$disks = $root_disk_size . 'GB';
			}
			else
			{
				$disks = null;
			}
		}
		else
		{
			$disks = implode(' + ', $disks);
		}

		return $disks;
	}
	protected static function prepend_disk_vendor($disk_model)
	{
		if(isset($disk_model[4]))
		{
			$disk_manufacturer = null;
			$third_char = substr($disk_model, 2, 1);

			switch(substr($disk_model, 0, 2))
			{
				case 'WD':
					$disk_manufacturer = 'Western Digital';

					if(substr($disk_model, 0, 4) == 'WDC ')
					{
						$disk_model = substr($disk_model, 4);
					}
					break;
				case 'MK':
					$disk_manufacturer = 'Toshiba';
					break;
				case 'HT':
					// 'HD' might be some Hitachi disk drives, but that prefix seems too common
					$disk_manufacturer = 'Hitachi';
					break;
				case 'ST':
					if($third_char == 'T')
					{
						$disk_manufacturer = 'Super Talent';
					}
					else if($third_char != 'E')
					{
						$disk_manufacturer = 'Seagate';
					}
					break;
			}

			if($disk_manufacturer != null && strpos($disk_model, $disk_manufacturer) === false)
			{
				$disk_model = $disk_manufacturer . ' ' . $disk_model;
			}

			// OCZ SSDs aren't spaced
			$disk_model = str_replace('OCZ-', 'OCZ ', $disk_model);
		}

		return $disk_model;
	}
	public static function hdd_scheduler()
	{
		$scheduler = null;

		if(is_readable('/sys/block/sda/queue/scheduler'))
		{
			$scheduler = pts_file_io::file_get_contents('/sys/block/sda/queue/scheduler');

			if(($s = strpos($scheduler, '[')) !== false && ($e = strpos($scheduler, ']', $s)) !== false)
			{
				$scheduler = strtoupper(substr($scheduler, $s + 1, $e - $s - 1));
			}
		}

		return $scheduler;
	}
}

?>
