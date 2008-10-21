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
	$hw_string = "Processor: " . processor_string() . " (Total Cores: " . cpu_core_count() . "), ";
	$hw_string .= "Motherboard: " . main_system_hardware_string() . ", ";
	$hw_string .= "Chipset: " . motherboard_chipset_string() . ", ";
	$hw_string .= "System Memory: " . system_memory_string() . ", ";
	$hw_string .= "Disk: " . system_hard_disks() . ", ";
	$hw_string .= "Graphics: " . graphics_processor_string() . graphics_frequency_string() . ", ";
	$hw_string .= "Screen Resolution: " . current_screen_resolution() . " ";

	return $hw_string;
}
function pts_sw_string()
{
	// Returns string of software information
	$sw_string = "OS: " . operating_system_release() . ", ";
	$sw_string .= "Kernel: " . kernel_string() . " (" . kernel_arch() . "), ";
	$sw_string .= "X.Org Server: " . graphics_subsystem_version() . ", ";
	$sw_string .= "OpenGL: " . opengl_version() . ", ";
	$sw_string .= "Compiler: " . compiler_version() . ", ";
	$sw_string .= "File-System: " . filesystem_type() . " ";

	return $sw_string;
}
function pts_system_identifier_string()
{
	$components = array(processor_string(), main_system_hardware_string(), operating_system_release(), compiler_version());
	return base64_encode(implode("__", $components));
}

?>
