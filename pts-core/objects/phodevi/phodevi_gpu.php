<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	phodevi_gpu.php: The PTS Device Interface object for the graphics processor

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

class phodevi_gpu extends pts_device_interface
{
	public static function read_property($identifier)
	{
		switch($identifier)
		{
			case "identifier":
				$property = new pts_device_property("phodevi_gpu", "gpu_string", true);
				break;
			case "model":
				$property = new pts_device_property("phodevi_gpu", "gpu_model", true);
				break;
			case "frequency":
				$property = new pts_device_property("phodevi_gpu", "gpu_frequency_string", true);
				break;
			case "stock-frequency":
				$property = new pts_device_property("phodevi_gpu", "gpu_stock_frequency", true);
				break;
			case "2d-accel-method":
				$property = new pts_device_property("phodevi_gpu", "gpu_2d_accel_method", true);
				break;
			case "memory-capacity":
				$property = new pts_device_property("phodevi_gpu", "gpu_memory_size", true);
				break;
			default:
				$property = new pts_device_property(null, null, false);
				break;
		}

		return $property;
	}
	public static function gpu_2d_accel_method()
	{
		$accel_method = "";

		if(is_file("/var/log/Xorg.0.log"))
		{
			$x_log = file_get_contents("/var/log/Xorg.0.log");

			if(strpos($x_log, "Using EXA") > 0)
			{
				$accel_method = "EXA";
			}
			else if(strpos($x_log, "Using UXA") > 0)
			{
				$accel_method = "UXA";
			}
			else if(strpos($x_log, "Using XFree86") > 0)
			{
				$accel_method = "XAA";
			}
		}

		return $accel_method;
	}
	public static function gpu_memory_size()
	{
		// Graphics memory capacity
		$video_ram = DEFAULT_VIDEO_RAM_CAPACITY;

		if(($vram = getenv("VIDEO_MEMORY")) != false && is_numeric($vram) && $vram > DEFAULT_VIDEO_RAM_CAPACITY)
		{
			$video_ram = $vram;
		}
		else
		{
			if(IS_NVIDIA_GRAPHICS && ($NVIDIA = read_nvidia_extension("VideoRam")) > 0) // NVIDIA blob
			{
				$video_ram = $NVIDIA / 1024;
			}
			else if(is_file("/var/log/Xorg.0.log"))
			{
				// Attempt Video RAM detection using X log
				// fglrx driver reports video memory to: (--) fglrx(0): VideoRAM: XXXXXX kByte, Type: DDR
				// xf86-video-ati, xf86-video-intel, and xf86-video-radeonhd also report their memory information in a similar format

				$info = shell_exec("cat /var/log/Xorg.0.log | grep -i VideoRAM");

				if(empty($info))
				{
					$info = shell_exec("cat /var/log/Xorg.0.log | grep \"Video RAM\"");
				}

				if(($pos = strpos($info, "RAM:")) > 0 || ($pos = strpos($info, "Ram:")) > 0)
				{
					$info = substr($info, $pos + 5);
					$info = substr($info, 0, strpos($info, " "));

					if($info > 65535)
					{
						$video_ram = intval($info) / 1024;
					}
				}
			}
			else if(IS_MACOSX)
			{
				$info = read_osx_system_profiler("SPDisplaysDataType", "VRAM");
				$info = explode(" ", $info);
				$video_ram = $info[0];
			
				if($info[1] == "GB")
				{
					$video_ram *= 1024;
				}
			}
		}

		return $video_ram;
	}
	public static function gpu_string()
	{
		return phodevi::read_property("gpu", "model") . phodevi::read_property("gpu", "frequency");
	}
	public static function gpu_frequency_string()
	{
		$freq = (IS_ATI_GRAPHICS ? phodevi::read_property("gpu", "stock-frequency") : hw_gpu_current_frequency());
		$freq_string = $freq[0] . "/" . $freq[1];

		return ($freq_string == "0/0" ? "" : " (" . $freq_string . "MHz)");
	}
	public static function function gpu_stock_frequency()
	{
		// Graphics processor stock frequency
		$core_freq = 0;
		$mem_freq = 0;

		if(IS_NVIDIA_GRAPHICS) // NVIDIA GPU
		{
			$nv_freq = read_nvidia_extension("GPUDefault3DClockFreqs");

			$nv_freq = explode(",", $nv_freq);
			$core_freq = $nv_freq[0];
			$mem_freq = $nv_freq[1];
		}
		else if(IS_ATI_GRAPHICS) // ATI GPU
		{
			$od_clocks = read_ati_overdrive("CurrentPeak");

			if(is_array($od_clocks) && count($od_clocks) >= 2) // ATI OverDrive
			{
				$core_freq = array_shift($od_clocks);
				$mem_freq = array_pop($od_clocks);
			}
		}

		if(!is_numeric($core_freq))
		{
			$core_freq = 0;
		}
		if(!is_numeric($mem_freq))
		{
			$mem_freq = 0;
		}

		return array($core_freq, $mem_freq);
	}
	public static function gpu_model()
	{
		// Report graphics processor string
		$info = shell_exec("glxinfo 2>&1 | grep renderer");
		$video_ram = phodevi::read_property("gpu", "memory-capacity");

		if(($pos = strpos($info, "renderer string:")) > 0)
		{
			$info = substr($info, $pos + 16);
			$info = trim(substr($info, 0, strpos($info, "\n")));
		}
		else
		{
			$info = "";
		}

		if(IS_ATI_GRAPHICS)
		{
			$crossfire_status = read_amd_pcsdb("SYSTEM/Crossfire/chain/*,Enable");
			$crossfire_status = pts_to_array($crossfire_status);
			$crossfire_card_count = 0;

			for($i = 0; $i < count($crossfire_status); $i++)
			{
				if($crossfire_status[$i] == "0x00000001")
				{
					$crossfire_card_count += 2; // For now assume each chain is 2 cards, but proper way would be NumSlaves + 1
				}
			}			

			$adapters = read_amd_graphics_adapters();

			if(count($adapters) > 0)
			{
				$video_ram = ($video_ram > DEFAULT_VIDEO_RAM_CAPACITY ? " " . $video_ram . "MB" : "");

				if($crossfire_card_count > 1 && $crossfire_card_count <= count($adapters))
				{
					$unique_adapters = array_unique($adapters);

					if(count($unique_adapters) == 1)
					{
						if(strpos($adapters[0], "X2") > 0 && $crossfire_card_count > 1)
						{
							$crossfire_card_count -= 1;
						}

						$info = $crossfire_card_count . " x " . $adapters[0] . $video_ram . " CrossFire";
					}
					else
					{
						$info = implode(", ", $unique_adapters) . " CrossFire";
					}
				}
				else
				{
					$info = $adapters[0] . $video_ram;
				}
			}
		}
		else if(IS_NVIDIA_GRAPHICS)
		{
			$sli_mode = read_nvidia_extension("SLIMode");

			if(!empty($sli_mode) && $sli_mode != "Off")
			{
				$info .= " SLI";
			}
		}

		if(IS_SOLARIS)
		{
			if(($cut = strpos($info, "DRI ")) !== false)
			{
				$info = substr($info, ($cut + 4));
			}
			if(($cut = strpos($info, " Chipset")) !== false)
			{
				$info = substr($info, 0, $cut);
			}

			$info = $info;
		}
		else if(IS_BSD)
		{
			$drm_info = read_sysctl("dev.drm.0.%desc");

			if($drm_info == false)
			{
				$agp_info = read_sysctl("dev.agp.0.%desc");

				if($agp_info != false)
				{
					$info = $agp_info;
				}
			}
			else
			{
				$info = $drm_info;
			}
		}
	
		if(empty($info) || strpos($info, "Mesa ") !== false || $info == "Software Rasterizer")
		{
			$log_parse = shell_exec("cat /var/log/Xorg.0.log 2>&1 | grep Chipset");
			$log_parse = substr($log_parse, strpos($log_parse, "Chipset") + 8);
			$log_parse = substr($log_parse, 0, strpos($log_parse, "found"));

			if(strpos($log_parse, "(--)") === false && strlen(str_replace(array("ATI", "NVIDIA", "VIA", "Intel"), "", $log_parse)) != strlen($log_parse))
			{
				$info = $log_parse;
			}
			else
			{
				$info_pci = read_pci("VGA compatible controller", false);

				if(!empty($info_pci))
				{
					$info = $info_pci;
				}

				if(($start_pos = strpos($info, " DRI ")) > 0)
				{
					$info = substr($info, $start_pos + 5);
				}
			}
		}

		if(IS_NVIDIA_GRAPHICS && $video_ram > DEFAULT_VIDEO_RAM_CAPACITY && strpos($info, $video_ram) == false)
		{
			$info .= " " . $video_ram . "MB";
		}
	
		$clean_phrases = array("OpenGL Engine");
		$info = str_replace($clean_phrases, "", $info);
		$info = pts_clean_information_string($info);
	
		if(IS_MACOSX)
		{
			$info .= " " . $video_ram . "MB";
		}

		return $info;
	}
}

?>
