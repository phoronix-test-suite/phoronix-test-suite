<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel
	pts-functions_system.php: Include system functions.

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

require_once("pts-core/functions/pts-functions_system_software.php");
require_once("pts-core/functions/pts-functions_system_hardware.php");
require_once("pts-core/functions/pts-functions_system_parsing.php");
require_once("pts-core/functions/pts-functions_system_cpu.php");
require_once("pts-core/functions/pts-functions_system_graphics.php");

function pts_hw_string()
{
	// Returns string of hardware information
	$hardware = array();

	array_push($hardware, "Processor: " . hw_cpu_string() . " (Total Cores: " . hw_cpu_core_count() . ")");
	array_push($hardware, "Motherboard: " . hw_sys_motherboard_string());
	array_push($hardware, "Chipset: " . hw_sys_chipset_string());
	array_push($hardware, "System Memory: " . hw_sys_memory_string());
	array_push($hardware, "Disk: " . hw_sys_hdd_string());
	array_push($hardware, "Graphics: " . hw_gpu_string() . hw_gpu_frequency());
	array_push($hardware, "Screen Resolution: " . hw_gpu_current_mode());

	return implode(", ", $hardware);
}
function pts_sw_string()
{
	// Returns string of software information
	$software = array();

	array_push($software, "OS: " . sw_os_release());
	array_push($software, "Kernel: " . sw_os_kernel() . " (" . sw_os_architecture() . ")");

	if(($desktop = sw_desktop_environment()) != "")
	{
		array_push($software, "Desktop: " . $desktop);
	}

	array_push($software, "X.Org Server: " . sw_os_graphics_subsystem());

	if(($ddx = sw_xorg_ddx_driver_info()) != "")
	{
		array_push($software, "X.Org Driver: " . $ddx);
	}

	array_push($software, "OpenGL: " . sw_os_opengl());
	array_push($software, "Compiler: " . sw_os_compiler());
	array_push($software, "File-System: " . sw_os_filesystem());

	return implode(", ", $software);
}
function pts_system_identifier_string()
{
	$components = array(hw_cpu_string(), hw_sys_motherboard_string(), sw_os_release(), sw_os_compiler());
	return base64_encode(implode("__", $components));
}

?>
