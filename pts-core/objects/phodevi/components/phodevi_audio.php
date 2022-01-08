<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010 - 2021, Phoronix Media
	Copyright (C) 2010 - 2021, Michael Larabel
	phodevi_audio.php: The PTS Device Interface object for audio / sound cards

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

class phodevi_audio extends phodevi_device_interface
{
	public static function properties()
	{
		return array(
			'identifier' => new phodevi_device_property('audio_processor_string', phodevi::smart_caching)
		);
	}
	public static function audio_processor_string()
	{
		$audio = null;

		if(phodevi::is_macos())
		{
			// TODO: implement
		}
		else if(phodevi::is_bsd())
		{
			foreach(array('dev.hdac.0.%desc') as $dev)
			{
				$dev = phodevi_bsd_parser::read_sysctl($dev);

				if(!empty($dev))
				{
					$audio = $dev;
				}
			}
		}
		else if(phodevi::is_windows())
		{
			$win_sound = array();
			$win32_sounddevice = shell_exec('powershell -NoProfile "(Get-WMIObject -Class win32_sounddevice | Select Name)"');
			if(($x = strpos($win32_sounddevice, '----')) !== false)
			{
				$win32_sounddevice = trim(substr($win32_sounddevice, $x + 4));
				foreach(explode("\n", $win32_sounddevice) as $sd)
				{
					if(!empty($sd))
					{
						$win_sound[] = $sd;
					}
				}
			}
			$win_sound = array_unique($win_sound);
			$audio = implode(' + ', $win_sound);
		}
		else if(phodevi::is_linux())
		{
			foreach(pts_file_io::glob('/sys/class/sound/card*/hwC0D*/vendor_name') as $vendor_name)
			{
				$card_dir = dirname($vendor_name) . '/';

				if(!is_readable($card_dir . 'chip_name'))
				{
					continue;
				}


				$vendor_name = pts_file_io::file_get_contents($vendor_name);
				$chip_name = pts_file_io::file_get_contents($card_dir . 'chip_name');

				$audio = $vendor_name . ' '. $chip_name;

				if(strpos($chip_name, 'HDMI') !== false || strpos($chip_name, 'DP') !== false)
				{
					// If HDMI is in the audio string, likely the GPU-provided audio, so try to find the mainboard otherwise
					$audio = null;
				}
				else
				{
					break;
				}
			}

			if($audio == null)
			{
				$audio = phodevi_linux_parser::read_pci('Multimedia audio controller');
			}

			if($audio == null)
			{
				$audio = phodevi_linux_parser::read_pci('Audio device');
			}
		}

		return $audio;
	}
}

?>
