<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2025, Phoronix Media
	Copyright (C) 2009 - 2025, Michael Larabel
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
		'Network' => 'network',
		'npu' => array('system', 'npu'),
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
	public static function determine_system_type($hw, $sw = null)
	{
		// Assume desktop by default as fallback
		$type = 'D';

		if(pts_strings::has_element_in_string($hw, array('Ampere ', 'Amazon EC2', 'Google Compute')))
		{
			// Dp this check first so ARM servers won't be classified as embedded E
			$type = 'S';
		}
		else if(pts_strings::has_element_in_string($hw, array('ARMv', 'Cortex', 'Exynos', 'jetson')) || stripos($sw, 'mips64') !== false)
		{
			$type = 'E';
		}
		else if(pts_strings::has_element_in_string($hw, array('Mobile ', 'M @', 'U @', 'M 2', 'macbook', 'thinkpad', 'color lcd')))
		{
			$type = 'M';
		}
		else if($hw == $sw && pts_strings::has_element_in_string($hw . ' ', array('Mobile ', 'M ', 'U ')) && pts_strings::has_element_in_string($hw, array('Intel ', 'AMD ')))
		{
			$type = 'M';
		}
		else if(strpos($sw, 'System Layer') !== false || stripos($sw, 'amazon') !== false || stripos($sw, 'xen') !== false || stripos($sw, 'qemu') !== false)
		{
			$type = 'V';
		}
		else if(pts_strings::has_element_in_string($hw, array('Quadro ', 'Tesla ', 'FirePro', 'Radeon Pro')) || (pts_strings::has_element_in_string($hw, array(' Xeon', 'Opteron', 'EPYC')) && strpos($sw, 'Desktop') && strpos($sw, 'OpenGL')))
		{
			$type = 'W';
		}
		else if(pts_strings::has_element_in_string($hw, array(' Xeon', 'Opteron', 'EPYC', 'POWER ', 'Ampere ')) || pts_strings::has_element_in_string($hw, array('Tyan', 'Supermicro')))
		{
			$type = 'S';
		}

		return $type;
	}
	public static function system_type_to_string($system_type)
	{
		switch($system_type)
		{
			case 'E':
				$t = 'Embedded';
				break;
			case 'M':
				$t = 'Mobile';
				break;
			case 'V':
				$t = 'Virtual';
				break;
			case 'S':
				$t = 'Server';
				break;
			case 'W':
				$t = 'Workstation';
				break;
			case 'D':
			default:
				$t = 'Desktop';
				break;
		}

		return $t;
	}
}

?>
