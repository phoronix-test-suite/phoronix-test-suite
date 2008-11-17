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
	array_push($hardware, "Motherboard: " . main_system_hardware_string());
	array_push($hardware, "Chipset: " . motherboard_chipset_string());
	array_push($hardware, "System Memory: " . system_memory_string());
	array_push($hardware, "Disk: " . system_hard_disks());
	array_push($hardware, "Graphics: " . graphics_processor_string() . graphics_frequency_string());
	array_push($hardware, "Screen Resolution: " . current_screen_resolution());

	return implode(", ", $hardware);
}
function pts_sw_string()
{
	// Returns string of software information
	$software = array();

	array_push($software, "OS: " . operating_system_release());
	array_push($software, "Kernel: " . kernel_string() . " (" . kernel_arch() . ")");
	array_push($software, "X.Org Server: " . graphics_subsystem_version());

	if(($ddx = xorg_ddx_driver_info()) != "")
	{
		array_push($software, "X.Org Driver: " . $ddx);
	}

	array_push($software, "OpenGL: " . opengl_version());
	array_push($software, "Compiler: " . compiler_version());
	array_push($software, "File-System: " . filesystem_type());

	return implode(", ", $software);
}
function pts_system_identifier_string()
{
	$components = array(hw_cpu_string(), main_system_hardware_string(), operating_system_release(), compiler_version());
	return base64_encode(implode("__", $components));
}

?>
