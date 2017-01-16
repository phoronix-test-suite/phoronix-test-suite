<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2017, Phoronix Media
	Copyright (C) 2009 - 2017, Michael Larabel
	phodevi_base.php: The base object for interacting with the Phoronix Device Interface

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

class phodevi_base
{
	public static function available_hardware_devices()
	{
		return array(
		'Processor' => 'cpu',
		'Motherboard' => 'motherboard',
		'Chipset' => 'chipset',
		'Memory' => 'memory',
		'Disk' => 'disk',
		'Graphics' => 'gpu',
		'Audio' => 'audio',
		'Monitor' => 'monitor',
		'Network' => 'network'
		);
	}
	public static function available_software_components()
	{
		return array(
		'OS' => array('system', 'operating-system'),
		'Kernel' => array('system', 'kernel-string'),
		'Desktop' => array('system', 'desktop-environment'),
		'Display Server' => array('system', 'display-server'),
		'Display Driver' => array('system', 'display-driver-string'),
		'OpenGL' => array('system', 'opengl-driver'),
		'OpenCL' => array('system', 'opencl-driver'),
		'Vulkan' => array('system', 'vulkan-driver'),
		'Compiler' => array('system', 'compiler'),
		'File-System' => array('system', 'filesystem'),
		'Screen Resolution' => array('gpu', 'screen-resolution-string'),
		'System Layer' => array('system', 'system-layer')
		);
	}
}

?>
