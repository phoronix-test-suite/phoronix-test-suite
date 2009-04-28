<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel
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

require_once(PTS_LIBRARY_PATH . "pts-functions_system_software.php");
require_once(PTS_LIBRARY_PATH . "pts-functions_system_hardware.php");
require_once(PTS_LIBRARY_PATH . "pts-functions_system_parsing.php");
require_once(PTS_LIBRARY_PATH . "pts-functions_system_cpu.php");
require_once(PTS_LIBRARY_PATH . "pts-functions_system_memory.php");
require_once(PTS_LIBRARY_PATH . "pts-functions_system_graphics.php");

function pts_hw_string($return_string = true)
{
	// Returns string of hardware information
	$hw = array();

	$hw["Processor"] = phodevi::read_name("cpu");
	$hw["Motherboard"] = phodevi::read_name("motherboard");
	$hw["Chipset"] = phodevi::read_name("chipset");
	$hw["System Memory"] = phodevi::read_name("memory");
	$hw["Disk"] = phodevi::read_name("disk");
	$hw["Graphics"] = phodevi::read_name("gpu");
	$hw["Monitor"] = phodevi::read_name("monitor");

	$hw = pts_remove_unsupported_entries($hw);

	return pts_process_string_array($return_string, $hw);
}
function pts_sw_string($return_string = true)
{
	// Returns string of software information
	$sw = array();

	$sw["OS"] = sw_os_release();
	$sw["Kernel"] = sw_os_kernel() . " (" . sw_os_architecture() . ")";
	$sw["Desktop"] = sw_desktop_environment();
	$sw["Display Server"] = sw_os_graphics_subsystem();
	$sw["Display Driver"] = sw_xorg_ddx_driver_info();
	$sw["OpenGL"] = sw_os_opengl();
	$sw["Compiler"] = sw_os_compiler();
	$sw["File-System"] = sw_os_filesystem();
	$sw["Screen Resolution"] = hw_gpu_current_mode();

	$sw = pts_remove_unsupported_entries($sw);

	return pts_process_string_array($return_string, $sw);
}
function pts_sys_sensors_string($return_string = true)
{
	$sensors = array();

	foreach(pts_supported_sensors() as $s)
	{
		$sensors[$s->get_formatted_hardware_type() . " " . $s->get_sensor_string()] = $s->read_sensor() . " " . $s->get_sensor_unit();
	}

	return pts_process_string_array($return_string, $sensors);
}
function pts_available_sensors()
{
	return array(
	new pts_sensor("temp", "gpu", "hw_gpu_temperature", "°C"),
	new pts_sensor("temp", "cpu", "hw_cpu_temperature", "°C"),
	new pts_sensor("temp", "hdd", "hw_sys_hdd_temperature", "°C"),
	new pts_sensor("temp", "sys", "hw_sys_temperature", "°C", "System"),
	new pts_sensor("battery", "power", "hw_sys_power_consumption_rate", "Milliwatts"),
	new pts_sensor("voltage", "cpu", array("hw_sys_line_voltage", "CPU"), "Volts"),
	new pts_sensor("voltage", "v3", array("hw_sys_line_voltage", "V3"), "Volts", "+3.33V"),
	new pts_sensor("voltage", "v5", array("hw_sys_line_voltage", "V5"), "Volts", "+5.00V"),
	new pts_sensor("voltage", "v12", array("hw_sys_line_voltage", "V12"), "Volts", "+12.00V"),
	new pts_sensor("freq", "cpu", "hw_cpu_current_frequency", "Megahertz"),
	new pts_sensor("freq", "gpu", array("hw_gpu_current_frequency", false), "Megahertz"),
	new pts_sensor("usage", "cpu", "hw_cpu_usage", "Percent"),
	new pts_sensor("usage", "gpu", "hw_gpu_core_usage", "Percent"),
	new pts_sensor("memory", "system", "sw_physical_memory_usage", "Megabytes"),
	new pts_sensor("memory", "swap", "sw_swap_memory_usage", "Megabytes"),
	new pts_sensor("memory", "total", "sw_total_memory_usage", "Megabytes")
	);
}
function pts_supported_sensors()
{
	$supported_sensors = array();

	foreach(pts_available_sensors() as $pts_sensor)
	{
		if($pts_sensor->read_sensor() != -1)
		{
			array_push($supported_sensors, $pts_sensor);
		}
	}

	return $supported_sensors;
}
function pts_remove_unsupported_entries($array)
{
	$clean_elements = array();

	foreach($array as $key => $value)
	{
		if($value != -1 && !empty($value))
		{
			$clean_elements[$key] = $value;
		}
	}

	return $clean_elements;
}
function pts_system_identifier_string()
{
	$components = array(phodevi::read_property("cpu", "model"), phodevi::read_name("motherboard"), sw_os_release(), sw_os_compiler());
	return base64_encode(implode("__", $components));
}

?>
