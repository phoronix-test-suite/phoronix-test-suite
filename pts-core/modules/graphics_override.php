<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2011, Phoronix Media
	Copyright (C) 2008 - 2011, Michael Larabel
	graphics_override.php: Graphics AA/AF image quality setting override module

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
	const module_version = "1.0.5";
	const module_description = "This module allows you to override some graphics rendering settings for the ATI and NVIDIA drivers while running the Phoronix Test Suite.";
	const module_author = "Michael Larabel";

	public static $module_store_vars = array("FORCE_AA", "FORCE_AF");

	static $preset_aa = FALSE;
	static $preset_af = FALSE;
	static $preset_aa_control = FALSE;
	static $preset_af_control = FALSE;

	static $supported_aa_levels = array(0, 2, 4, 8, 16);
	static $supported_af_levels = array(0, 2, 4, 8, 16);

	public static function module_environmental_variables()
	{
		return array('FORCE_AA', 'FORCE_AF');
	}
	public static function set_nvidia_extension($attribute, $value)
	{
		// Sets an object in NVIDIA's NV Extension
		if(phodevi::is_nvidia_graphics())
		{
			shell_exec("nvidia-settings --assign " . $attribute . "=" . $value . " 2>&1");
		}
	}
	public static function __pre_run_process()
	{
		if(!phodevi::is_nvidia_graphics())
		{
			echo "\nNo supported driver found for graphics_override module!\n";
			return pts_module::MODULE_UNLOAD; // Not using a supported driver, quit the module
		}

		$force_aa = pts_module::read_variable("FORCE_AA");
		$force_af = pts_module::read_variable("FORCE_AF");

		if($force_aa !== FALSE && in_array($force_aa, self::$supported_aa_levels))
		{
			// First backup any existing override, then set the new value
			if(phodevi::is_nvidia_graphics())
			{
				self::$preset_aa = phodevi_parser::read_nvidia_extension("FSAA");
				self::$preset_aa_control = phodevi_parser::read_nvidia_extension("FSAAAppControlled");

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

				if(isset($nvidia_aa))
				{
					self::set_nvidia_extension("FSAA", $nvidia_aa);
					self::set_nvidia_extension("FSAAAppControlled", 0);
				}
			}
		}

		if($force_af !== FALSE && in_array($force_af, self::$supported_af_levels))
		{
			// First backup any existing override, then set the new value
			if(phodevi::is_nvidia_graphics())
			{
				self::$preset_af = phodevi_parser::read_nvidia_extension("LogAniso");
				self::$preset_af_control = phodevi_parser::read_nvidia_extension("LogAnisoAppControlled");

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

				if(isset($nvidia_af))
				{
					self::set_nvidia_extension("LogAniso", $nvidia_af);
					self::set_nvidia_extension("LogAnisoAppControlled", 0);
				}
			}
		}
	}
	public static function __post_option_process()
	{
		if(phodevi::is_nvidia_graphics())
		{
			if(self::$preset_aa !== FALSE)
			{
				self::set_nvidia_extension("FSAA", self::$preset_aa);
				self::set_nvidia_extension("FSAAAppControlled", self::$preset_aa_control);
			}
			if(self::$preset_af !== FALSE)
			{
				self::set_nvidia_extension("LogAniso", self::$preset_af);
				self::set_nvidia_extension("LogAnisoAppControlled", self::$preset_af_control);
			}
		}
	}
}

?>
