<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
	pts-functions_system_graphics.php: System functions related to graphics.

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

define("DEFAULT_VIDEO_RAM_CAPACITY", 128);

function hw_gpu_temperature()
{
	// Report graphics processor temperature
	if(IS_NVIDIA_GRAPHICS)
	{
		$temp_c = read_nvidia_extension("GPUCoreTemp");
	}
	else if(IS_ATI_GRAPHICS)
	{
		$temp_c = read_ati_overdrive("Temperature");
	}
	else
	{
		$temp_c = false;
	}

	return (is_numeric($temp_c) ? $temp_c : -1);
}
function hw_gpu_set_resolution($width, $height)
{
	shell_exec("xrandr -s " . $width . "x" . $height . " 2>&1");

	return phodevi::read_property("gpu", "screen-resolution") == array($width, $height); // Check if video resolution set worked
}
function hw_gpu_set_nvidia_extension($attribute, $value)
{
	// Sets an object in NVIDIA's NV Extension
	if(IS_NVIDIA_GRAPHICS)
	{
		shell_exec("nvidia-settings --assign " . $attribute . "=" . $value . " 2>&1");
	}
}
function hw_gpu_set_amd_pcsdb($attribute, $value)
{
	// Sets a value for AMD's PCSDB, Persistent Configuration Store Database
	if(IS_ATI_GRAPHICS && !empty($value))
	{
		$DISPLAY = substr(getenv("DISPLAY"), 1, 1);
		$info = shell_exec("DISPLAY=:" . $DISPLAY . " aticonfig --set-pcs-val=" . $attribute . "," . $value . "  2>&1");
	}
}
function hw_gpu_current_frequency($show_memory = true)
{
	// Graphics processor real/current frequency
	$core_freq = 0;
	$mem_freq = 0;

	if(IS_NVIDIA_GRAPHICS) // NVIDIA GPU
	{
		$nv_freq = read_nvidia_extension("GPUCurrentClockFreqs");

		$nv_freq = explode(",", $nv_freq);
		$core_freq = $nv_freq[0];
		$mem_freq = $nv_freq[1];
	}
	else if(IS_ATI_GRAPHICS) // ATI GPU
	{
		$od_clocks = read_ati_overdrive("CurrentClocks");

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

	return ($show_memory ? array($core_freq, $mem_freq) : $core_freq);
}
function hw_gpu_core_usage()
{
	// Determine GPU usage
	$gpu_usage = -1;

	if(IS_ATI_GRAPHICS)
	{
		$gpu_usage = read_ati_overdrive("GPUload");
	}

	return $gpu_usage;
}

?>
