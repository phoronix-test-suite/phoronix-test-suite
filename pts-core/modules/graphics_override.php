<?php

/*
	Phoronix Test Suite "Trondheim"
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2004-2008, Michael Larabel
	pts_module_interface.php: The generic Phoronix Test Suite module object that is extended by the specific modules/plug-ins

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

class graphics_override extends pts_module_interface
{
	const module_name = "Graphics Override";
	const module_version = "1.0.1";
	const module_description = "This module allows you to override some graphics rendering settings for the ATI and NVIDIA drivers while running the Phoronix Test Suite.";
	const module_author = "Michael Larabel";

	public static $module_store_vars = array("FORCE_AA", "FORCE_AF");

	static $graphics_driver = FALSE;
	static $preset_aa = FALSE;
	static $preset_af = FALSE;
	static $preset_aa_control = FALSE;
	static $preset_af_control = FALSE;

	static $supported_aa_levels = array(0, 2, 4, 8, 16);
	static $supported_af_levels = array(0, 2, 4, 8, 16);

	public static function __pre_run_process()
	{
		$opengl_driver = opengl_version();

		if(strpos($opengl_driver, "NVIDIA") != FALSE)
			self::$graphics_driver = "NVIDIA";
		else if(strpos($opengl_driver, "fglrx") != FALSE)
			self::$graphics_driver = "fglrx";
		else
			echo "\nNo supported driver found for graphics_override module!\n";

		if(self::$graphics_driver == FALSE)
			return; // Not using a supported driver

		$force_aa = trim(getenv("FORCE_AA"));
		$force_af = trim(getenv("FORCE_AF"));

		if($force_aa !== FALSE && in_array($force_aa, self::$supported_aa_levels))
		{
			// First backup any existing override, then set the new value
			if(self::$graphics_driver == "NVIDIA")
			{
				self::$preset_aa = read_nvidia_extension("FSAA");
				self::$preset_aa_control = read_nvidia_extension("FSAAAppControlled");

				switch($force_aa)
				{
					case 2:
						$nvidia_aa = 2;
						break;
					case 4:
						$nvidia_aa = 5;
						break;
					case 8:
						$nvidia_aa = 7;
						break;
					case 16:
						$nvidia_aa = 8;
						break;
				}
				set_nvidia_extension("FSAA", $nvidia_aa);
				set_nvidia_extension("FSAAAppControlled", 0);
			}
			else if(self::$graphics_driver == "fglrx")
			{
				self::$preset_aa = read_amd_pcsdb("OpenGL,AntiAliasSamples");
				self::$preset_aa_control = read_amd_pcsdb("OpenGL,AAF");

				switch($force_aa)
				{
					case 2:
						$ati_aa = "0x00000002";
						break;
					case 4:
						$ati_aa = "0x00000004";
						break;
					case 8:
						$ati_aa = "0x00000008";
						break;
					case 16:
						echo "\nThe ATI fglrx driver currently doesn't support 16x AA! Defaulting to 8x AA!\n";
						$ati_aa = "0x00000008";
						break;
				}
				set_amd_pcsdb("OpenGL,AntiAliasSamples", $ati_aa);
				set_amd_pcsdb("OpenGL,AAF", "0x00000000");
			}
		}

		if($force_af !== FALSE && in_array($force_af, self::$supported_af_levels))
		{
			// First backup any existing override, then set the new value
			if(self::$graphics_driver == "NVIDIA")
			{
				self::$preset_af = read_nvidia_extension("LogAniso");
				self::$preset_af_control = read_nvidia_extension("LogAnisoAppControlled");

				switch($force_af)
				{
					case 2:
						$nvidia_af = 1;
						break;
					case 4:
						$nvidia_af = 2;
						break;
					case 8:
						$nvidia_af = 3;
						break;
					case 16:
						$nvidia_af = 4;
						break;
				}
				set_nvidia_extension("LogAniso", $nvidia_af);
				set_nvidia_extension("LogAnisoAppControlled", 0);
			}
			else if(self::$graphics_driver == "fglrx")
			{
				self::$preset_af = read_amd_pcsdb("OpenGL,AnisoDegree");

				switch($force_af)
				{
					case 2:
						$ati_af = "0x00000002";
						break;
					case 4:
						$ati_af = "0x00000004";
						break;
					case 8:
						$ati_af = "0x00000008";
						break;
					case 16:
						$ati_af = "0x00000010";
						break;
				}
				set_amd_pcsdb("OpenGL,AnisoDegree", $ati_af);
			}
		}
	}
	public static function __shutdown()
	{
		if(self::$graphics_driver == FALSE)
			return; // Not using a supported graphics driver

		if(self::$graphics_driver == "NVIDIA")
		{
			if(self::$preset_aa !== FALSE)
			{
				set_nvidia_extension("FSAA", self::$preset_aa);
				set_nvidia_extension("FSAAAppControlled", self::$preset_aa_control);
			}
			if(self::$preset_af !== FALSE)
			{
				set_nvidia_extension("LogAniso", self::$preset_af);
				set_nvidia_extension("LogAnisoAppControlled", self::$preset_af_control);
			}
		}
		else if(self::$graphics_driver == "fglrx")
		{
			if(self::$preset_aa !== FALSE)
			{
				set_amd_pcsdb("OpenGL,AntiAliasSamples", self::$preset_aa);
				set_amd_pcsdb("OpenGL,AAF", self::$preset_aa_control);
			}
			if(self::$preset_af !== FALSE)
			{
				set_amd_pcsdb("OpenGL,AnisoDegree", self::$preset_af);
			}
		}
	}
}

?>
