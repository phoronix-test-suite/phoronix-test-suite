<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2024, Phoronix Media
	Copyright (C) 2008 - 2024, Michael Larabel
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

class phodevi_gpu extends phodevi_device_interface
{
	public static function properties()
	{
		return array(
			'identifier' => new phodevi_device_property('gpu_string', phodevi::std_caching),
			'model' => new phodevi_device_property('gpu_model', phodevi::smart_caching),
			'frequency' => new phodevi_device_property('gpu_frequency_string', phodevi::std_caching),
			'stock-frequency' => new phodevi_device_property('gpu_stock_frequency', phodevi::std_caching),
			'memory-capacity' => new phodevi_device_property('gpu_memory_size', phodevi::smart_caching),
			'oc-offset-string' => new phodevi_device_property('gpu_oc_offset_string', phodevi::no_caching),
			'aa-level' => new phodevi_device_property('gpu_aa_level', phodevi::no_caching),
			'af-level' => new phodevi_device_property('gpu_af_level', phodevi::no_caching),
			'compute-cores' => new phodevi_device_property('gpu_compute_cores', phodevi::smart_caching),
			'available-modes' => new phodevi_device_property('gpu_available_modes', phodevi::std_caching),
			'screen-resolution' => new phodevi_device_property('gpu_screen_resolution', phodevi::std_caching),
			'screen-resolution-string' => new phodevi_device_property('gpu_screen_resolution_string', phodevi::std_caching),
			'2d-acceleration' => new phodevi_device_property('gpu_2d_acceleration', phodevi::std_caching),
			'bar1-visible-vram' => new phodevi_device_property('bar1_visible_vram', phodevi::smart_caching),
			'vbios-version' => new phodevi_device_property('vbios_version', phodevi::smart_caching),
			'device-id' => new phodevi_device_property('gpu_pci_device_id', phodevi::smart_caching),
			);
	}
	public static function bar1_visible_vram()
	{
		$rebar_size = '';
		if(phodevi::is_nvidia_graphics())
		{
			if(pts_client::executable_in_path('nvidia-smi'))
			{
				$nvidia_smi = shell_exec('nvidia-smi -a | grep BAR -A 3');

				if(($x = strpos($nvidia_smi, 'Total ')) !== false)
				{
					$nvidia_smi = substr($nvidia_smi, $x);
					$nvidia_smi = substr($nvidia_smi, strpos($nvidia_smi, ': ') + 2);
					$nvidia_smi = substr($nvidia_smi, 0, strpos($nvidia_smi, PHP_EOL));
					$rebar_size = $nvidia_smi;
				}
			}
		}
		if(phodevi::is_linux() && pts_client::executable_in_path('glxinfo'))
		{
			// Could improve detection above to only AMD Radeon graphics...
			$amd_debug_glxinfo = shell_exec('AMD_DEBUG=info glxinfo 2>&1 | grep vram');

			if($amd_debug_glxinfo != null && ($x = strpos($amd_debug_glxinfo, 'vram_vis_size = ')) !== false)
			{
				$amd_debug_glxinfo = substr($amd_debug_glxinfo, $x + 16);
				$amd_debug_glxinfo = substr($amd_debug_glxinfo, 0, strpos($amd_debug_glxinfo, PHP_EOL));
				$rebar_size = $amd_debug_glxinfo;
			}
		}

		return $rebar_size;
	}
	public static function vbios_version()
	{
		$vbios_version = '';
		if(phodevi::is_nvidia_graphics())
		{
			foreach(pts_file_io::glob('/proc/driver/nvidia/gpus/*/information') as $nvidia_info)
			{
				$nvidia_info = file_get_contents($nvidia_info);
				if(($vbios_line = strpos($nvidia_info, 'Video BIOS')) !== false)
				{
					$nvidia_info = substr($nvidia_info, $vbios_line + 12);
					if(($vbios_line = strpos($nvidia_info, PHP_EOL)) !== false)
					{
						$nvidia_info = trim(substr($nvidia_info, 0, $vbios_line));
						$vbios_version = $nvidia_info;
					}
				}
			}
		}
		else if(is_file('/sys/class/drm/card0/device/vbios_version'))
		{
			$vbios_version = pts_file_io::file_get_contents('/sys/class/drm/card0/device/vbios_version');
		}

		return $vbios_version;
	}
	public static function gpu_pci_device_id()
	{
		$device_id = null;
		if(phodevi::is_nvidia_graphics())
		{
			$nvidia_id = explode(',', phodevi_parser::read_nvidia_extension('PCIID'));
			if(!empty($nvidia_id))
			{
				$device_id = array_pop($nvidia_id);
			}
		}
		else if(phodevi::is_linux())
		{
			foreach(pts_file_io::glob('/sys/class/drm/card*/device/device') as $device)
			{
				$device_id = str_replace('0x', '', pts_file_io::file_get_contents($device));
				if(!empty($device_id))
				{
					break;
				}
			}
		}
		else if(phodevi::is_windows())
		{
			$pnputil = shell_exec('pnputil /enum-devices /bus PCI /deviceids /class "Display"');
			if(($x = strpos($pnputil, '&DEV_')) !== false)
			{
				$pnputil = substr($pnputil, $x + 5);
				if(is_numeric(substr($pnputil, 0, 4)))
				{
					$device_id = substr($pnputil, 0, 4);
				}
			}
		}

		return $device_id;
	}
	public static function gpu_2d_acceleration()
	{
		$xorg_log = isset(phodevi::$vfs->xorg_log) ? phodevi::$vfs->xorg_log : false;
		$accel_2d = false;

		if($xorg_log)
		{
			if(strpos($xorg_log, 'EXA(0)'))
			{
				$accel_2d = 'EXA';
			}
			else if(stripos($xorg_log, 'GLAMOR acceleration'))
			{
				$accel_2d = 'GLAMOR';
			}
			else if(strpos($xorg_log, 'SNA initialized'))
			{
				$accel_2d = 'SNA';
			}
			else if(strpos($xorg_log, 'UXA(0)'))
			{
				$accel_2d = 'UXA';
			}
			else if(strpos($xorg_log, 'Gallium3D XA'))
			{
				$accel_2d = 'Gallium3D XA';
			}
			else if(strpos($xorg_log, 'shadowfb'))
			{
				$accel_2d = 'ShadowFB';
			}
		}

		return $accel_2d;
	}
	public static function set_property($identifier, $args)
	{
		switch($identifier)
		{
			case 'screen-resolution':
				$property = self::gpu_set_resolution($args);
				break;
		}

		return $property;
	}
	public static function gpu_set_resolution($args)
	{
		if(count($args) != 2 || phodevi::is_windows() || phodevi::is_macos() || !pts_client::executable_in_path('xrandr'))
		{
			return false;
		}

		$width = $args[0];
		$height = $args[1];

		shell_exec('xrandr -s ' . $width . 'x' . $height . ' 2>&1');

		return phodevi::read_property('gpu', 'screen-resolution') == array($width, $height); // Check if video resolution set worked
	}
	public static function gpu_oc_offset_string()
	{
		$offset = 0;

		if(is_file('/sys/class/drm/card0/device/pp_sclk_od'))
		{
			// AMDGPU OverDrive
			$pp_sclk_od = pts_file_io::file_get_contents('/sys/class/drm/card0/device/pp_sclk_od');
			if(is_numeric($pp_sclk_od) && $pp_sclk_od > 0)
			{
				$offset = 'AMD OverDrive GPU Overclock: ' . $pp_sclk_od . '%';
			}
		}

		return $offset;
	}
	public static function gpu_aa_level()
	{
		// Determine AA level if over-rode
		$aa_level = false;

		if(phodevi::is_nvidia_graphics())
		{
			$nvidia_fsaa = phodevi_parser::read_nvidia_extension('FSAA');

			switch($nvidia_fsaa)
			{
				case 1:
					$aa_level = '2x Bilinear';
					break;
				case 5:
					$aa_level = '4x Bilinear';
					break;
				case 7:
					$aa_level = '8x';
					break;
				case 8:
					$aa_level = '16x';
					break;
				case 10:
					$aa_level = '8xQ';
					break;
				case 12:
					$aa_level = '16xQ';
					break;
			}
		}
		else if(phodevi::is_mesa_graphics())
		{
			$gallium_msaa = getenv('GALLIUM_MSAA');
			if(is_numeric($gallium_msaa) && $gallium_msaa > 0)
			{
				// Simple test to try to figure out if the GALLIUM_MSAA anti-aliasing value was forced
				$aa_level = $gallium_msaa . 'x MSAA';
			}
		}
		else if(getenv('__GL_FSAA_MODE'))
		{
			$gl_msaa = getenv('__GL_FSAA_MODE');
			if(is_numeric($gl_msaa) && $gl_msaa > 0)
			{
				$aa_level = '__GL_FSAA_MODE=' . $gl_msaa;
			}
		}

		return $aa_level;
	}
	public static function gpu_af_level()
	{
		// Determine AF level if over-rode
		$af_level = false;

		if(phodevi::is_nvidia_graphics())
		{
			$nvidia_af = phodevi_parser::read_nvidia_extension('LogAniso');

			switch($nvidia_af)
			{
				case 1:
					$af_level = '2x';
					break;
				case 2:
					$af_level = '4x';
					break;
				case 3:
					$af_level = '8x';
					break;
				case 4:
					$af_level = '16x';
					break;
			}
		}
		else if(getenv('__GL_LOG_MAX_ANISO'))
		{
			$max_aniso = getenv('__GL_LOG_MAX_ANISO');
			if(is_numeric($max_aniso) && $max_aniso > 0)
			{
				switch($max_aniso)
				{
					case 1:
						$max_aniso = '2x';
						break;
					case 2:
						$max_aniso = '4x';
						break;
					case 3:
						$max_aniso = '8x';
						break;
					case 4:
						$max_aniso = '16x';
						break;
				}

				$af_level = $max_aniso;
			}
		}

		return $af_level;
	}
	public static function gpu_compute_cores()
	{
		// Determine AF level if over-rode
		$cores = 0;

		if(phodevi::is_nvidia_graphics())
		{
			$cores = phodevi_parser::read_nvidia_extension('CUDACores');
		}

		return $cores;
	}
	public static function gpu_xrandr_resolution()
	{
		$resolution = false;

		if(pts_client::executable_in_path('xrandr') && getenv('DISPLAY'))
		{
			// Read resolution from xrandr
			// First try reading "current" screen 0 as it should better handle multiple monitors, etc.
			// e.g. Screen 0: minimum 1 x 1, current 2560 x 1341, maximum 8192 x 8192
			$info = shell_exec('xrandr 2>&1');
			if(!empty($info))
			{
				$info = substr($info, strpos($info, 'current ') + 8);
				$info = explode(' x ', trim(substr($info, 0, strpos($info, ','))));
			}

			if(count($info) == 2 && is_numeric($info[0]) && is_numeric($info[1]))
			{
				$resolution = $info;
			}

			if($resolution == false)
			{
				$info = shell_exec('xrandr 2>&1 | grep "*"');

				if(!empty($info) && strpos($info, '*') !== false)
				{
					$res = pts_strings::trim_explode('x', $info);

					if(isset($res[1]))
					{
						$res[0] = substr($res[0], strrpos($res[0], ' '));
						$res[1] = substr($res[1], 0, strpos($res[1], ' '));
						$res = array_map('trim', $res);

						if(is_numeric($res[0]) && is_numeric($res[1]))
						{
							$resolution = array($res[0], $res[1]);
						}
					}
				}
			}
		}

		return $resolution;
	}
	public static function gpu_screen_resolution()
	{
		$resolution = false;

		if((($default_mode = getenv('DEFAULT_VIDEO_MODE')) != false))
		{
			$default_mode = explode('x', $default_mode);

			if(count($default_mode) == 2 && is_numeric($default_mode[0]) && is_numeric($default_mode[1]))
			{
				return $default_mode;
			}
		}

		if(phodevi::is_macos())
		{
			$info = pts_strings::trim_explode(' ', phodevi_osx_parser::read_osx_system_profiler('SPDisplaysDataType', 'Resolution'));
			$resolution = array();
			$resolution[0] = $info[0];
			$resolution[1] = $info[2];
		}
		else if(phodevi::is_linux() || phodevi::is_bsd() || phodevi::is_solaris())
		{
			if($resolution == false && pts_client::executable_in_path('xrandr'))
			{
				$resolution = self::gpu_xrandr_resolution();
			}

			if($resolution == false && phodevi::is_linux())
			{
				// Before calling xrandr first try to get the resolution through KMS path
				foreach(pts_file_io::glob('/sys/class/drm/card*/*/modes') as $connector_path)
				{
					$connector_path = dirname($connector_path) . '/';

					if(is_file($connector_path . 'enabled') && pts_file_io::file_get_contents($connector_path . 'enabled') == 'enabled')
					{
						$mode = pts_arrays::first_element(explode("\n", pts_file_io::file_get_contents($connector_path . 'modes')));
						$info = pts_strings::trim_explode('x', $mode);

						if(count($info) == 2)
						{
							$resolution = $info;
							break;
						}
					}
				}
			}

			if($resolution == false && phodevi::is_nvidia_graphics())
			{
				// Way to find resolution through NVIDIA's NV-CONTROL extension
				// But rely upon xrandr first since when using NVIDIA TwinView the reported FrontEndResolution may be the smaller of the two
				if(($frontend_res = phodevi_parser::read_nvidia_extension('FrontendResolution')) != false)
				{
					$resolution = pts_strings::comma_explode($frontend_res);
				}
			}

			if($resolution == false)
			{
				// Fallback to reading resolution from xdpyinfo
				foreach(phodevi_parser::read_xdpy_monitor_info() as $monitor_line)
				{
					$this_resolution = substr($monitor_line, strpos($monitor_line, ': ') + 2);
					$this_resolution = substr($this_resolution, 0, strpos($this_resolution, ' '));
					$this_resolution = explode('x', $this_resolution);

					if(count($this_resolution) == 2 && is_numeric($this_resolution[0]) && is_numeric($this_resolution[1]))
					{
						$resolution = $this_resolution;
						break;
					}
				}
			}

			if($resolution == false && is_readable('/sys/class/graphics/fb0/virtual_size'))
			{
				// As last fall-back try reading size of fb
				$virtual_size = explode(',', pts_file_io::file_get_contents('/sys/class/graphics/fb0/virtual_size'));

				if(count($virtual_size) == 2 && is_numeric($virtual_size[0]) && is_numeric($virtual_size[1]))
				{
					$resolution = $virtual_size;
				}
			}

			if($resolution == false && phodevi::is_bsd())
			{
				if(($x = strpos(phodevi::$vfs->dmesg, 'VT(efifb): resolution ')) !== false)
				{
					$info = substr(phodevi::$vfs->dmesg, $x + 22);
					$info = trim(substr($info, 0, strpos($info, PHP_EOL)));
					$res = explode('x', $info);
					if(count($res) == 2 && is_numeric($res[0]) && is_numeric($res[1]))
					{
						$resolution = $res;
					}
				}
			}
		}
		else if(phodevi::is_windows())
		{
			$resolution = array(phodevi_windows_parser::get_wmi_object('Win32_VideoController', 'CurrentHorizontalResolution'), phodevi_windows_parser::get_wmi_object('Win32_VideoController', 'CurrentVerticalResolution'));
		}

		return $resolution == false ? array(-1, -1) : $resolution;
	}
	public static function gpu_screen_resolution_string()
	{
		// Return the current screen resolution
		$resolution = implode('x', phodevi::read_property('gpu', 'screen-resolution'));

		if($resolution == '-1x-1')
		{
			$resolution = null;
		}

		return $resolution;
	}
	public static function gpu_available_modes()
	{
		// XRandR available modes
		$current_resolution = phodevi::read_property('gpu', 'screen-resolution');
		$current_pixel_count = $current_resolution[0] * $current_resolution[1];
		$available_modes = array();
		$ignore_modes = array(
			array(640, 400),
			array(720, 480), array(832, 624),
			array(960, 540), array(960, 600),
			array(896, 672), array(928, 696),
			array(960, 720), array(1152, 864),
			array(1280, 720), array(1360, 768),
			array(1776, 1000), array(1792, 1344),
			array(1800, 1440), array(1856, 1392),
			array(2048, 1536)
			);

		if($override_check = (($override_modes = getenv('OVERRIDE_VIDEO_MODES')) != false))
		{
			$override_modes = pts_strings::comma_explode($override_modes);

			for($i = 0; $i < count($override_modes); $i++)
			{
				$override_modes[$i] = explode('x', $override_modes[$i]);
			}
		}

		// Attempt reading available modes from xrandr
		if(pts_client::executable_in_path('xrandr') && !phodevi::is_macos()) // MacOSX has xrandr but currently on at least my setup will emit a Bus Error when called
		{
			$xrandr_lines = array_reverse(explode("\n", shell_exec('xrandr 2>&1')));

			foreach($xrandr_lines as $xrandr_mode)
			{
				if(($cut_point = strpos($xrandr_mode, '(')) > 0)
				{
					$xrandr_mode = substr($xrandr_mode, 0, $cut_point);
				}

				$res = pts_strings::trim_explode('x', $xrandr_mode);

				if(count($res) == 2)
				{
					$res[0] = substr($res[0], strrpos($res[0], ' '));
					$res[1] = substr($res[1], 0, strpos($res[1], ' '));

					if(is_numeric($res[0]) && is_numeric($res[1]))
					{
						$m = array($res[0], $res[1]);
						if(!in_array($m, $available_modes))
						{
							// Don't repeat modes
							array_push($available_modes, $m);
						}
					}
				}
			}
		}

		if(true) // XXX can remove this if it turns out to be fine
		{
			// Fallback to providing stock modes
			$stock_modes = array(
				array(800, 600),
				array(1024, 768),
				array(1280, 1024),
				array(1600, 1200),
				array(1920, 1080),
				array(1920, 1200),
				array(2560, 1440),
				array(3840, 2160));
			$available_modes = array();

			for($i = 0; $i < count($stock_modes); $i++)
			{
				if($stock_modes[$i][0] <= $current_resolution[0] && $stock_modes[$i][1] <= $current_resolution[1])
				{
					array_push($available_modes, $stock_modes[$i]);
				}
			}
		}

		if(!in_array(phodevi::read_property('gpu', 'screen-resolution'), $available_modes))
		{
			array_push($available_modes, phodevi::read_property('gpu', 'screen-resolution'));
		}

		foreach($available_modes as $mode_index => $mode)
		{
			if($override_check && !in_array($mode, $override_modes))
			{
				// Using override modes and this mode is not present
				unset($available_modes[$mode_index]);
			}
			else if($current_pixel_count > 614400 && ($mode[0] * $mode[1]) < 480000 && stripos(phodevi::read_name('gpu'), 'llvmpipe') === false)
			{
				// For displays larger than 1024 x 600, drop modes below 800 x 600 unless llvmpipe is being used
				unset($available_modes[$mode_index]);
			}
			else if(in_array($mode, $ignore_modes))
			{
				// Mode is to be ignored
				unset($available_modes[$mode_index]);
			}
		}

		// Sort available modes in order
		$unsorted_modes = $available_modes;
		$available_modes = array();
		$mode_pixel_counts = array();

		foreach($unsorted_modes as $this_mode)
		{
			if(count($this_mode) == 2)
			{
				array_push($mode_pixel_counts, $this_mode[0] * $this_mode[1]);
			}
		}

		// Sort resolutions by true pixel count resolution
		sort($mode_pixel_counts);
		foreach($mode_pixel_counts as &$mode_pixel_count)
		{
			foreach($unsorted_modes as $mode_index => $mode)
			{
				if($mode[0] * $mode[1] == $mode_pixel_count)
				{
					array_push($available_modes, $mode);
					unset($unsorted_modes[$mode_index]);
					break;
				}
			}
		}

		if(count($available_modes) == 0 && $override_check)
		{
			// Write in the non-standard modes that were overrode
			foreach($override_modes as $mode)
			{
				if(is_array($mode) && count($mode) == 2)
				{
					array_push($available_modes, $mode);
				}
			}
		}

		return $available_modes;
	}
	public static function gpu_memory_size()
	{
		// Graphics memory capacity
		$video_ram = -1;

		if(($vram = getenv('VIDEO_MEMORY')) != false && is_numeric($vram))
		{
			$video_ram = $vram;
		}
		else if(is_file('/sys/kernel/debug/dri/0/memory'))
		{
			// This is how some of the Nouveau DRM VRAM is reported
			$memory = file_get_contents('/sys/kernel/debug/dri/0/memory');

			if(($x = strpos($memory, 'VRAM total: ')) !== false)
			{
				$memory = substr($memory, ($x + 12));

				if(($x = strpos($memory, 'KiB')) !== false)
				{
					$memory = substr($memory, 0, $x);

					if(is_numeric($memory))
					{
						$video_ram = $memory / 1024;
					}
				}
			}
		}
		else if(phodevi::is_nvidia_graphics() && ($NVIDIA = phodevi_parser::read_nvidia_extension('VideoRam')) > 0) // NVIDIA blob
		{
			$video_ram = $NVIDIA / 1024;
		}
		else if(($nvidia_smi = pts_client::executable_in_path('nvidia-smi')))
		{
			$smi_output = shell_exec(escapeshellarg($nvidia_smi) . ' -q -d MEMORY');
			$mem = strpos($smi_output, 'Total');
			if($mem !== false)
			{
				$mem = substr($smi_output, strpos($smi_output, ':', $mem) + 1);
				$mem = trim(substr($mem, 0, strpos($mem, 'MiB')));

				if(is_numeric($mem) && $mem > 0)
				{
					$video_ram = $mem;
				}
			}
		}
		else if(phodevi::is_macos())
		{
			$info = phodevi_osx_parser::read_osx_system_profiler('SPDisplaysDataType', 'VRAM');
			$info = explode(' ', $info);
			$video_ram = $info[0];

			if(isset($info[1]) && $info[1] == 'GB')
			{
				$video_ram *= 1024;
			}
		}
		else if(phodevi::is_windows())
		{
			$video_ram = phodevi_windows_parser::get_wmi_object('Win32_VideoController', 'AdapterRAM');
			if(is_numeric($video_ram) && $video_ram > 1048576)
			{
				$video_ram = $video_ram / 1048576;
			}
		}
		else
		{
			// Try reading video memoty from GLX_MESA_query_renderer output in glxinfo
			$glxinfo_video_mem = str_replace('MB', '', phodevi_parser::glxinfo_read_line('Video memory'));

			if(is_numeric($glxinfo_video_mem) && $glxinfo_video_mem > 1)
			{
				// Do some rounding as at least AMDGPU/RadeonSI reports less than real amount
				$video_ram = $glxinfo_video_mem % 128 === 0 ? $glxinfo_video_mem : round(($glxinfo_video_mem + 128 / 2) / 128) * 128;
			}
		}

		if($video_ram == -1 && isset(phodevi::$vfs->dmesg))
		{
			// Fallback to try to find vRAM from dmesg
			$info = phodevi::$vfs->dmesg;

			if(($x = strpos($info, 'Detected VRAM RAM=')) !== false)
			{
				// Radeon DRM at least reports: [drm] Detected VRAM RAM=2048M, BAR=256M
				$info = substr($info, $x + 18);
				$info = substr($info, 0, strpos($info, 'M'));
			}
			else if(($x = strpos($info, 'M of VRAM')) !== false)
			{
				// Radeon DRM around Linux ~3.0 reports e.g.: [drm] radeon: 2048M of VRAM memory ready
				$info = substr($info, 0, $x);
				$info = substr($info, strrpos($info, ' ') + 1);
			}
			else if(($x = strpos($info, 'MiB VRAM')) !== false)
			{
				// Nouveau DRM around Linux ~3.0 reports e.g.: [drm] nouveau XXX: Detected 1024MiB VRAM
				$info = substr($info, 0, $x);
				$info = substr($info, strrpos($info, ' ') + 1);
			}
			else if(($x = strpos($info, 'VRAM: ')) !== false)
			{
				// Nouveau DRM around Linux ~3.6 reports e.g.: DRM] VRAM: 512 MiB
				$info = substr($info, ($x + 6));
				$info = substr($info, 0, strpos($info, ' '));

				if(substr($info, -1) == 'M')
				{
					$info = substr($info, 0, -1);
				}
			}

			if(is_numeric($info))
			{
				$video_ram = $info;
			}
		}

		if($video_ram == -1 || !is_numeric($video_ram) || $video_ram < 64)
		{
			$video_ram = 64; // default to 64MB of video RAM as something sane...
		}

		return $video_ram;
	}
	public static function gpu_string()
	{
		$info = phodevi_parser::read_glx_renderer();

		if(stripos($info, 'llvmpipe'))
		{
			return 'LLVMpipe';
		}
		else
		{
			$model = phodevi::read_property('gpu', 'model');
			$freq_string = null;
			if(stripos($model, 'NVIDIA') === false)
			{
				// 2021: NVIDIA dynamic frequency handling is too sporadic, wish there was a way to query peak freq
				$freq_string = phodevi::read_property('gpu', 'frequency');
			}
			return $model . ($freq_string != null ? ' (' . $freq_string . ')' : null);
		}
	}
	public static function gpu_frequency_string()
	{
		$freq = phodevi::read_property('gpu', 'stock-frequency');
		$freq_string = null;

		if($freq[0] != 0)
		{
			$freq_string = $freq[0];

			if($freq[1] != 0)
			{
				$freq_string .= '/' . $freq[1];
			}

			$freq_string .= 'MHz';
		}

		return $freq_string;
	}
	public static function gpu_stock_frequency()
	{
		// Graphics processor stock frequency
		$core_freq = 0;
		$mem_freq = 0;

		if(phodevi::is_nvidia_graphics() && phodevi::is_macos() == false) // NVIDIA GPU
		{
			// GPUDefault3DClockFreqs is the default and does not show under/over-clocking
			$clock_freqs_3d = pts_strings::comma_explode(phodevi_parser::read_nvidia_extension('GPU3DClockFreqs'));
			$clock_freqs_current = pts_strings::comma_explode(phodevi_parser::read_nvidia_extension('GPUCurrentClockFreqs'));

			if(is_array($clock_freqs_3d) && isset($clock_freqs_3d[1]))
			{
				list($core_freq, $mem_freq) = $clock_freqs_3d;
			}
			if(is_array($clock_freqs_current) && isset($clock_freqs_current[1]))
			{
				$core_freq = max($core_freq, $clock_freqs_current[0]);
				$mem_freq = max($mem_freq, $clock_freqs_current[1]);
			}
		}
		else if(phodevi::is_linux()) // More liberally attempt open-source freq detection than phodevi::is_mesa_graphics()
		{
			if(is_file('/sys/class/drm/card0/device/performance_level'))
			{
				// NOUVEAU
				/*
					EXAMPLE OUTPUTS:
					memory 1000MHz core 500MHz voltage 1300mV fanspeed 100%
					3: memory 333MHz core 500MHz shader 1250MHz fanspeed 100%
					c: memory 333MHz core 500MHz shader 1250MHz
				*/

				$performance_level = pts_file_io::file_get_contents('/sys/class/drm/card0/device/performance_level');
				$performance_level = explode(' ', $performance_level);

				$core_string = array_search('core', $performance_level);
				if($core_string !== false && isset($performance_level[($core_string + 1)]))
				{
					$core_string = str_ireplace('MHz', '', $performance_level[($core_string + 1)]);
					if(is_numeric($core_string))
					{
						$core_freq = $core_string;
					}
				}
				$mem_string = array_search('memory', $performance_level);
				if($mem_string !== false && isset($performance_level[($mem_string + 1)]))
				{
					$mem_string = str_ireplace('MHz', '', $performance_level[($mem_string + 1)]);
					if(is_numeric($mem_string))
					{
						$mem_freq = $mem_string;
					}
				}
			}
			else if(is_file('/sys/class/drm/card0/device/pstate'))
			{
				// Nouveau
				// pstate is present with Linux 3.13 as the new performance states on Fermi/Kepler
				$performance_state = pts_file_io::file_get_contents('/sys/class/drm/card0/device/pstate');
				$performance_level = substr($performance_state, 0, strpos($performance_state, ' *'));
				if($performance_level == null)
				{
					// Method for Linux 3.17+
					$performance_level = substr($performance_state, strpos($performance_state, 'AC: ') + 4);
					if(($t = strpos($performance_level, PHP_EOL)))
					{
						$performance_level = substr($performance_level, 0, $t);
					}
				}
				else
				{
					// Method for Linux ~3.13 through Linux 3.16
					$performance_level = substr($performance_level, strrpos($performance_level, ': ') + 2);
				}

				$performance_level = explode(' ', $performance_level);
				$core_string = array_search('core', $performance_level);
				if($core_string !== false && isset($performance_level[($core_string + 1)]))
				{
					$core_string = str_ireplace('MHz', '', $performance_level[($core_string + 1)]);
					if(strpos($core_string, '-') !== false)
					{
						// to work around a range of values, e.g.
						// 0a: core 405-1032 MHz memory 1620 MHz AC DC *
						$core_string = max(explode('-', $core_string));
					}
					if(is_numeric($core_string))
					{
						$core_freq = $core_string;
					}
				}
				$mem_string = array_search('memory', $performance_level);
				if($mem_string !== false && isset($performance_level[($mem_string + 1)]))
				{
					$mem_string = str_ireplace('MHz', '', $performance_level[($mem_string + 1)]);
					if(strpos($mem_string, '-') !== false)
					{
						// to work around a range of values, e.g.
						// 0a: core 405-1032 MHz memory 1620 MHz AC DC *
						$mem_string = max(explode('-', $mem_string));
					}
					if(is_numeric($mem_string))
					{
						$mem_freq = $mem_string;
					}
				}

			}

			//
			// RADEON / AMDGPU Logic
			//

			if(isset(phodevi::$vfs->radeon_pm_info))
			{
				// radeon_pm_info should be present with Linux 2.6.34+ but was changed with Linux 3.11 Radeon DPM
				if(stripos(phodevi::$vfs->radeon_pm_info, 'default'))
				{
					foreach(pts_strings::trim_explode("\n", phodevi::$vfs->radeon_pm_info) as $pm_line)
					{
						if($pm_line == null)
						{
							continue;
						}
						list($descriptor, $value) = pts_strings::colon_explode($pm_line);

						switch($descriptor)
						{
							case 'default engine clock':
								$core_freq = pts_arrays::first_element(explode(' ', $value)) / 1000;
								break;
							case 'default memory clock':
								$mem_freq = pts_arrays::first_element(explode(' ', $value)) / 1000;
								break;
						}
					}
				}
				if($core_freq == 0 && ($x = stripos(phodevi::$vfs->radeon_pm_info, 'sclk: ')) != false)
				{
					$x = substr(phodevi::$vfs->radeon_pm_info, $x + strlen('sclk: '));
					$x = substr($x, 0, strpos($x, ' '));
					if(is_numeric($x) && $x > 100)
					{
						if($x > 10000)
						{
							$x = $x / 100;
						}
						$core_freq = $x;
					}
					if(($x = stripos(phodevi::$vfs->radeon_pm_info, 'mclk: ')) != false)
					{
						$x = substr(phodevi::$vfs->radeon_pm_info, $x + strlen('mclk: '));
						$x = substr($x, 0, strpos($x, ' '));
						if(is_numeric($x) && $x > 100)
						{
							if($x > 10000)
							{
								$x = $x / 100;
							}
							$mem_freq = $x;
						}
					}
				}
			}
			if($core_freq == null && is_file('/sys/class/drm/card0/device/pp_dpm_sclk'))
			{
				$pp = trim(file_get_contents('/sys/class/drm/card0/device/pp_dpm_sclk'));
				$pp = explode("\n", $pp);
				$pp = array_pop($pp);
				if(($x = strpos($pp, ': ')) !== false)
				{
					$pp = substr($pp, $x + 2);
				}
				$pp = trim(str_replace(array('*', 'Mhz'), '', $pp));
				if(is_numeric($pp))
				{
					$core_freq = $pp;

					if(is_file('/sys/class/drm/card0/device/pp_dpm_mclk'))
					{
						$pp = trim(file_get_contents('/sys/class/drm/card0/device/pp_dpm_mclk'));
						$pp = explode("\n", $pp);
						$pp = array_pop($pp);
						if(($x = strpos($pp, ': ')) !== false)
						{
							$pp = substr($pp, $x + 2);
						}
						$pp = trim(str_replace(array('*', 'Mhz'), '', $pp));
						if(is_numeric($pp))
						{
							$mem_freq = $pp;
						}
					}
				}
			}
			if($core_freq == null && isset(phodevi::$vfs->dmesg) && strrpos(phodevi::$vfs->dmesg, ' sclk:'))
			{
				// Attempt to read the LAST power level reported to dmesg, this is the current way for Radeon DPM on Linux 3.11+
				$dmesg_parse = phodevi::$vfs->dmesg;
				if(($x = strrpos($dmesg_parse, ' sclk:')))
				{
					$dmesg_parse = substr($dmesg_parse, $x);
					$dmesg_parse = explode(' ', substr($dmesg_parse, 0, strpos($dmesg_parse, PHP_EOL)));

					$sclk = array_search('sclk:', $dmesg_parse);
					if($sclk !== false && isset($dmesg_parse[($sclk + 1)]) && is_numeric($dmesg_parse[($sclk + 1)]))
					{
						$sclk = $dmesg_parse[($sclk + 1)];
						if($sclk > 10000)
						{
							$sclk = $sclk / 100;
						}
						$core_freq = $sclk;
					}
					$mclk = array_search('mclk:', $dmesg_parse);
					if($mclk !== false && isset($dmesg_parse[($mclk + 1)]) && is_numeric($dmesg_parse[($mclk + 1)]))
					{
						$mclk = $dmesg_parse[($mclk + 1)];
						if($mclk > 10000)
						{
							$mclk = $mclk / 100;
						}
						$mem_freq = $mclk;
					}
				}
			}

			//
			// INTEL
			//

			// try to read the maximum dynamic frequency
			if($core_freq == 0 && is_file('/sys/class/drm/card0/gt_max_freq_mhz'))
			{
				$gt_max_freq_mhz = pts_file_io::file_get_contents('/sys/class/drm/card0/gt_max_freq_mhz');
				if(is_numeric($gt_max_freq_mhz) && $gt_max_freq_mhz > 100)
				{
					// Tested on Linux 3.11. Assume the max frequency on any competent GPU is beyond 100MHz
					$core_freq = $gt_max_freq_mhz;
				}
			}
			if($core_freq == 0 && is_file('/sys/kernel/debug/dri/0/i915_max_freq'))
			{
				$i915_max_freq = pts_file_io::file_get_contents('/sys/kernel/debug/dri/0/i915_max_freq');
				$freq_mhz = substr($i915_max_freq, strpos($i915_max_freq, ': ') + 2);
				if(is_numeric($freq_mhz))
				{
					$core_freq = $freq_mhz;
				}
			}
			// Fallback to base frequency
			if($core_freq == 0 && isset(phodevi::$vfs->i915_cur_delayinfo))
			{
				$i915_cur_delayinfo = phodevi::$vfs->i915_cur_delayinfo;
				$freq = strpos($i915_cur_delayinfo, 'Max overclocked frequency: ');

				if($freq === false)
				{
					$freq = strpos($i915_cur_delayinfo, 'Max non-overclocked (RP0) frequency: ');
				}
				if($freq === false)
				{
					$freq = strpos($i915_cur_delayinfo, 'Nominal (RP1) frequency: ');
				}

				if($freq !== false)
				{
					$freq_mhz = substr($i915_cur_delayinfo, strpos($i915_cur_delayinfo, ': ', $freq) + 2);
					$freq_mhz = trim(substr($freq_mhz, 0, strpos($freq_mhz, 'MHz')));
					if(is_numeric($freq_mhz))
					{
						$core_freq = $freq_mhz;
					}
				}
			}
		}
		else if(($nvidia_smi = pts_client::executable_in_path('nvidia-smi')))
		{
			$smi_output = shell_exec(escapeshellarg($nvidia_smi) . ' -q -d CLOCK');
			$mem = strpos($smi_output, 'Max Clocks');
			if($mem !== false)
			{
				$core_clock = substr($smi_output, stripos($smi_output, 'Graphics', $mem) + 9);
				$core_freq = trim(str_replace(':', '', substr($core_clock, 0, strpos($core_clock, 'MHz'))));
				$mem_clock = substr($smi_output, stripos($smi_output, 'Memory', $mem) + 8);
				$mem_freq = trim(str_replace(':', '', substr($mem_clock, 0, strpos($mem_clock, 'MHz'))));
			}
		}

		$core_freq = !is_numeric($core_freq) ? 0 : round($core_freq);
		$mem_freq = !is_numeric($mem_freq) ? 0 : round($mem_freq);

		return array($core_freq, $mem_freq);
	}
	public static function gpu_model()
	{
		// Report graphics processor string
		$info = str_replace('(R)', '', phodevi_parser::read_glx_renderer());
		$video_ram = phodevi::read_property('gpu', 'memory-capacity');

		if(phodevi::is_macos())
		{
			$system_profiler_info = implode(' + ', phodevi_osx_parser::read_osx_system_profiler('SPDisplaysDataType', 'ChipsetModel', true));

			if(!empty($system_profiler_info))
			{
				$info = $system_profiler_info;
			}
		}
		else if(phodevi::is_nvidia_graphics())
		{
			if($info == null)
			{
				if(pts_client::executable_in_path('nvidia-settings'))
				{
					$nv_gpus = shell_exec('nvidia-settings -q gpus 2>&1');

					// TODO: search for more than one GPU
					$nv_gpus = substr($nv_gpus, strpos($nv_gpus, '[0]'));
					$nv_gpus = substr($nv_gpus, strpos($nv_gpus, '(') + 1);
					$nv_gpus = substr($nv_gpus, 0, strpos($nv_gpus, ')'));

					if(stripos($nv_gpus, 'GeForce') !== false || stripos($nv_gpus, 'Quadro') !== false)
					{
						$info = $nv_gpus;
					}
				}
			}

			$sli_mode = phodevi_parser::read_nvidia_extension('SLIMode');

			if(!empty($sli_mode) && $sli_mode != 'Off')
			{
				$info .= ' SLI';
			}
		}
		else if(phodevi::is_solaris())
		{
			if(($cut = strpos($info, 'DRI ')) !== false)
			{
				$info = substr($info, ($cut + 4));
			}

			if(($cut = strpos($info, ' Chipset')) !== false)
			{
				$info = substr($info, 0, $cut);
			}

			if($info == false && isset(phodevi::$vfs->xorg_log))
			{
				$xorg_log = phodevi::$vfs->xorg_log;
				if(($x = strpos($xorg_log, '(0): Chipset: ')) !== false)
				{
					$xorg_log = substr($xorg_log, ($x + 14));
					$xorg_log = str_replace(array('(R)', '"'), '', substr($xorg_log, 0, strpos($xorg_log, PHP_EOL)));

					if(($c = strpos($xorg_log, '[')) || ($c = strpos($xorg_log, '(')))
					{
						$xorg_log = substr($xorg_log, 0, $c);
					}

					if(phodevi::is_product_string($xorg_log))
					{
						$info = $xorg_log;
					}
				}

			}
		}
		else if(phodevi::is_bsd())
		{
			$drm_info = phodevi_bsd_parser::read_sysctl('dev.drm.0.%desc');

			if(!$drm_info)
			{
				$drm_info = phodevi_bsd_parser::read_sysctl('dev.nvidia.0.%desc');
			}

			if(!$drm_info)
			{
				$agp_info = phodevi_bsd_parser::read_sysctl('dev.agp.0.%desc');

				if($agp_info != false)
				{
					$info = $agp_info;
				}
			}
			else
			{
				$info = $drm_info;
			}

			if($info == null && isset(phodevi::$vfs->xorg_log))
			{
				$xorg_log = phodevi::$vfs->xorg_log;
				if(($e = strpos($xorg_log, ' at 01@00:00:0')) !== false)
				{
					$xorg_log = substr($xorg_log, 0, $e);
					$info = substr($xorg_log, strrpos($xorg_log, 'Found ') + 6);
				}
			}

			if($info == null)
			{
				$info = phodevi_bsd_parser::read_pciconf_by_class('display');


				if(empty($info) || strlen($info) > 60)
				{
					$info = null;
				}
			}
		}
		else if(phodevi::is_windows())
		{
			$windows_gpu = phodevi_windows_parser::get_wmi_object_multi('Win32_VideoController', 'Name');
			if(count($windows_gpu) > 1 && ($x = array_search('Microsoft Basic Display Adapter', $windows_gpu)) !== false)
			{
				unset($windows_gpu[$x]);
			}
			$info = str_replace('(TM)', '', implode(' + ', $windows_gpu));
		}

		if(empty($info) || strpos($info, 'Mesa ') !== false || strpos($info, 'Gallium ') !== false || strpos($info, 'DRM ') !== false)
		{
			if(!empty($info))
			{
				if(($x = strpos($info, ' on ')) !== false)
				{
					// to remove section like "Gallium 0.4 on AMD POLARIS"
					$info = substr($info, $x + 4);
				}
				if(strpos($info, 'Intel ') !== false)
				{
					// Intel usually has e.g. TGL GT2 or other info within
					$info = str_replace(array('(', ')'), '', $info);
				}
				if(($x = strpos($info, ' (')) !== false)
				{
					$info = substr($info, 0, $x);
				}
			}

			if(phodevi::is_windows() == false && (empty($info) || (strpos($info, 'Intel ') === false && !pts_strings::string_contains($info, pts_strings::CHAR_NUMERIC))))
			{
				$controller_3d = phodevi_linux_parser::read_pci('3D controller', false);
				$info_pci = phodevi_linux_parser::read_pci('VGA compatible controller', false);

				if((empty($info_pci) || strpos($info_pci, ' ') === false || stripos($info_pci, 'aspeed') !== false) && !empty($controller_3d))
				{
					// e.g. NVIDIA GH200 is 3D controller while VGA is ASpeed
					if(stripos($controller_3d, 'nvidia') !== false && ($nvidia_smi = pts_client::executable_in_path('nvidia-smi')))
					{
						// This works for some headless configurations
						$nvidia_smi = shell_exec($nvidia_smi . ' -L 2>&1');
						if(($x = strpos($nvidia_smi, 'GPU 0: ')) !== false)
						{
							$nvidia_smi = substr($nvidia_smi, $x + 7);

							if(($x = strpos($nvidia_smi, PHP_EOL)) !== false)
							{
								$nvidia_smi = substr($nvidia_smi, 0, $x);
							}

							$nvidia_smi = trim($nvidia_smi);
							if(!empty($nvidia_smi))
							{
								$info_pci = $nvidia_smi;
							}
						}
					}
					else
					{
						$info_pci = $controller_3d;
					}
				}

				if(!empty($info_pci) && strpos($info_pci, 'Device ') === false)
				{
					$info = $info_pci;

					if(strpos($info, 'Intel 2nd Generation Core Family') !== false || strpos($info, 'Gen Core') !== false)
					{
						// Try to come up with a better non-generic string
						$was_reset = false;
						if(isset(phodevi::$vfs->xorg_log))
						{
							/*
							$ cat /var/log/Xorg.0.log | grep -i Chipset
							[     8.421] (II) intel: Driver for Intel Integrated Graphics Chipsets: i810,
							[     8.421] (II) VESA: driver for VESA chipsets: vesa
							[     8.423] (II) intel(0): Integrated Graphics Chipset: Intel(R) Sandybridge Mobile (GT2+)
							[     8.423] (--) intel(0): Chipset: "Sandybridge Mobile (GT2+)"
							*/

							$xorg_log = phodevi::$vfs->xorg_log;
							if(($x = strpos($xorg_log, 'Integrated Graphics Chipset: ')) !== false)
							{
								$xorg_log = substr($xorg_log, ($x + 29));
								$xorg_log = str_replace(array('(R)', '"'), '', substr($xorg_log, 0, strpos($xorg_log, PHP_EOL)));

								if(stripos($xorg_log, 'Intel') === false)
								{
									$xorg_log = 'Intel ' . $xorg_log;
								}

								// if string is too long, likely not product
								if(!isset($xorg_log[45]))
								{
									$info = $xorg_log;
									$was_reset = true;
								}
							}
							else if(($x = strpos($xorg_log, '(0): Chipset: ')) !== false)
							{
								$xorg_log = substr($xorg_log, ($x + 14));
								$xorg_log = str_replace(array('(R)', '"'), '', substr($xorg_log, 0, strpos($xorg_log, PHP_EOL)));

								if(stripos($xorg_log, 'Intel') === false)
								{
									$xorg_log = 'Intel ' . $xorg_log;
								}

								// if string is too long, likely not product
								if(!isset($xorg_log[45]))
								{
									$info = $xorg_log;
									$was_reset = true;
								}
							}
						}
						if($was_reset == false && isset(phodevi::$vfs->i915_capabilities))
						{
							$i915_caps = phodevi::$vfs->i915_capabilities;
							if(($x = strpos($i915_caps, 'gen: ')) !== false)
							{
								$gen = substr($i915_caps, ($x + 5));
								$gen = substr($gen, 0, strpos($gen, PHP_EOL));

								if(is_numeric($gen))
								{
									$info = 'Intel Gen' . $gen;

									if(strpos($i915_caps, 'is_mobile: yes') !== false)
									{
										$info .= ' Mobile';
									}
								}
							}
						}
					}
				}
			}

			if(!empty($info) && ($start_pos = strpos($info, ' DRI ')) > 0)
			{
				$info = substr($info, $start_pos + 5);
			}

			if(empty($info) && isset(phodevi::$vfs->xorg_log))
			{
				$log_parse = phodevi::$vfs->xorg_log;
				$log_parse = substr($log_parse, strpos($log_parse, 'Chipset') + 8);
				$log_parse = substr($log_parse, 0, strpos($log_parse, 'found'));

				if(strpos($log_parse, '(--)') === false && strlen(str_ireplace(array('ATI', 'NVIDIA', 'VIA', 'Intel'), '', $log_parse)) != strlen($log_parse))
				{
					$info = $log_parse;
				}
			}

			if(empty($info) && is_readable('/sys/class/graphics/fb0/name'))
			{
				switch(pts_file_io::file_get_contents('/sys/class/graphics/fb0/name'))
				{
					case 'omapdrm':
						$info = 'Texas Instruments OMAP'; // The OMAP DRM driver currently is for OMAP2/3/4 hardware
						break;
					case 'exynos':
						$info = 'Samsung EXYNOS'; // The Exynos DRM driver
						break;
					case 'tegra_fb':
						$info = 'NVIDIA TEGRA'; // The Exynos DRM driver
						break;
					default:
						if(is_file('/dev/mali'))
						{
							$info = 'ARM Mali'; // One of the ARM Mali models
						}
						break;
				}

			}

			if(!empty($info) && substr($info, -1) == ')' && ($open_p = strrpos($info, '(')) != false)
			{
				$end_check = strpos($info, ' ', $open_p);
				$to_check = substr($info, ($open_p + 1), ($end_check - $open_p - 1));

				// Don't report card revision from PCI info
				if($to_check == 'rev')
				{
					$info = substr($info, 0, $open_p - 1);
				}
			}
		}

		if(empty($info) && (($nvidia_smi = pts_client::executable_in_path('nvidia-smi')) || ($nvidia_smi = pts_client::executable_in_path('nvidia-smi.exe'))))
		{
			// This works for some headless configurations or with Windows WSL2
			$nvidia_smi = shell_exec($nvidia_smi . ' -L 2>&1');
			if(($x = strpos($nvidia_smi, 'GPU 0: ')) !== false)
			{
				$nvidia_smi = substr($nvidia_smi, $x + 7);

				if(($x = strpos($nvidia_smi, PHP_EOL)) !== false)
				{
					$nvidia_smi = substr($nvidia_smi, 0, $x);
				}

				$info = trim($nvidia_smi);
			}
		}

		if(!empty($info) && ($x = strpos($info, ' (')) !== false)
		{
			$info = substr($info, 0, $x);
		}

		if(empty($info) && isset(phodevi::$vfs->xorg_log))
		{
			// ARM Mali DDX driver detection fallback
			if(strpos(phodevi::$vfs->xorg_log, 'MALI(') !== false)
			{
				$info = 'ARM Mali';
			}
		}

		if(!empty($info))
		{
			if(($bracket_open = strpos($info, '[')) !== false)
			{
				// Report only the information inside the brackets if it's more relevant...
				// Mainly with Linux systems where the PCI information is reported like 'nVidia GF104 [GeForce GTX 460]'

				if(($bracket_close = strpos($info, ']', ($bracket_open + 1))) !== false)
				{
					$inside_bracket = substr($info, ($bracket_open + 1), ($bracket_close - $bracket_open - 1));

					if(stripos($inside_bracket, 'Quadro') !== false || stripos($inside_bracket, 'GeForce') !== false)
					{
						$info = $inside_bracket . ' ' . substr($info, ($bracket_close + 1));
					}
					else if(stripos($inside_bracket, 'Radeon') !== false || stripos($inside_bracket, 'Fire') !== false || stripos($inside_bracket, 'Fusion') !== false)
					{
						$info = $inside_bracket . ' ' . substr($info, ($bracket_close + 1));
					}
				}
			}

			if(stripos($info, 'NVIDIA') === false && (stripos($info, 'Quadro') !== false || stripos($info, 'Titan ') !== false ||  stripos($info, ' Tesla') !== false || stripos($info, 'GeForce') !== false || substr($info, 0, 2) == 'NV'))
			{
				$info = 'NVIDIA' . ' ' . $info;
			}
			else if((stripos($info, 'ATI') === false && stripos($info, 'AMD') === false) && (stripos($info, 'Radeon') !== false || stripos($info, 'Fire') !== false || stripos($info, 'Fusion') !== false))
			{
				// Fire would be for FireGL or FirePro hardware
				$info = 'AMD ' . $info;
			}

			if(phodevi::is_linux() && ($vendor = phodevi_linux_parser::read_pci_subsystem_value('VGA compatible controller')) != null && stripos($info, $vendor) === false && (stripos($info, 'AMD') !== false || stripos($info, 'NVIDIA') !== false || stripos($info, 'Intel') !== false))
			{
				$info = $vendor . ' ' . $info;
			}

			$clean_phrases = array('OpenGL Engine');
			$info = str_replace($clean_phrases, '', $info);

			if(!empty($info) && $video_ram > 64 && strpos($info, $video_ram) == false && stripos($info, 'llvmpipe') === false && substr($info, -2) != 'GB') // assume more than 64MB of vRAM
			{
				if($video_ram < 1024)
				{
					$info .= ' ' . $video_ram . 'MB';
				}
				else
				{
					$video_ram = round($video_ram / 1024) . 'GB';
					if(strpos($info, $video_ram) == false)
					{
						$info .= ' ' . $video_ram;
					}
				}
			}
		}

		if(empty($info) && is_dir('/sys/class/drm/card0/device/driver/pvrsrvkm'))
		{
			$info = 'PowerVR';
		}
		if(empty($info) && is_readable('/sys/class/graphics/fb0/name'))
		{
			// Last possible fallback...
			$info = str_replace(' FB', '', pts_file_io::file_get_contents('/sys/class/graphics/fb0/name'));
		}

		if(!empty($info))
		{
			// Happens with Intel Iris Gallium3D
			$info = str_replace('Mesa ', ' ', $info);
		}
		/*if(empty($info))
		{
			$info = 'Unknown';
		}*/

		return $info;
	}
}

?>
