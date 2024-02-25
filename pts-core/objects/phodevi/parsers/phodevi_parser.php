<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2017, Phoronix Media
	Copyright (C) 2008 - 2017, Michael Larabel
	phodevi_parser.php: General parsing functions used by different parts of Phodevi that are supported by more than one OS

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

class phodevi_parser
{
	public static function read_nvidia_extension($attribute)
	{
		// Read NVIDIA's NV Extension
		$nv_info = false;

		if(pts_client::executable_in_path('nvidia-settings'))
		{
			$info = shell_exec('nvidia-settings --query ' . $attribute . ' 2> /dev/null');

			if(empty($info))
			{
				return false;
			}

			if(($pos = strpos($info, pts_strings::last_in_string($attribute, '/'))) > 0 && strpos($info, 'ERROR:') === false)
			{
				$nv_info = substr($info, strpos($info, '):') + 3);
				$nv_info = trim(substr($nv_info, 0, strpos($nv_info, "\n")));

				if(substr($nv_info, -1) == '.')
				{
					$nv_info = substr($nv_info, 0, -1);
				}
			}
		}

		return $nv_info;
	}
	public static function read_ipmi_sensor($name)
	{
		$ipmi_info = -1;

		if(pts_client::executable_in_path('ipmiutil'))
		{
			$info = shell_exec('ipmiutil sensor 2> /dev/null');

			if(($pos = strpos($info, $name)) !== false)
			{
				$info = substr($info, $pos);
				$info = substr($info, 0, strpos($info, PHP_EOL));
				$info = trim(substr($info, strrpos($info, 'OK') + 2));
				$info = substr($info, 0, strpos($info, ' '));

				if(is_numeric($info) && $info >= 0)
				{
					$ipmi_info = $info;
				}
			}
		}

		return $ipmi_info;
	}
	public static function read_xdpy_monitor_info()
	{
		// Read xdpyinfo monitor information
		return array();
		static $monitor_info = null;

		if($monitor_info == null)
		{
			$monitor_info = array();

			if(pts_client::executable_in_path('xdpyinfo'))
			{
				$info = trim(shell_exec('xdpyinfo -ext XINERAMA 2>&1 | grep head'));

				foreach(explode("\n", $info) as $xdpyinfo_line)
				{
					if(!empty($xdpyinfo_line) && strpos($xdpyinfo_line, '0x0') == false)
					{
						array_push($monitor_info, $xdpyinfo_line);
					}
				}
			}
		}

		return $monitor_info;
	}
	public static function read_glx_renderer()
	{
		if(isset(phodevi::$vfs->glxinfo))
		{
			$info = phodevi::$vfs->glxinfo;
		}
		else if(PTS_IS_CLIENT && pts_client::executable_in_path('fglrxinfo'))
		{
			$info = shell_exec('fglrxinfo 2>&1');
		}
		else
		{
			return false;
		}

		if(($pos = strpos($info, 'OpenGL renderer string:')) !== false)
		{
			$info = substr($info, $pos + 24);
			$info = trim(substr($info, 0, strpos($info, "\n")));
		}
		else
		{
			$info = false;
		}

		if(stripos($info, 'Software Rasterizer') !== false)
		{
			$info = false;
		}

		return str_ireplace(array('Mesa DRI ', '(tm)'), '', $info);
	}
	public static function read_hddtemp($disk = null)
	{
		// Read hard drive temperature using hddtemp
		$hdd_temperature = -1;

		if(pts_client::executable_in_path('hddtemp'))
		{
			if(empty($disk))
			{
				$disks = glob('/dev/sd*');

				if(count($disks) > 0)
				{
					$disk = array_shift($disks);
				}
			}

			// For most situations this won't work since hddtemp usually requires root access
			$info = trim(shell_exec('hddtemp ' . $disk . ' 2>&1'));

			if(($start_pos = strrpos($info, ': ')) > 0 && ($end_pos = strrpos($info, '°')) > $start_pos)
			{
				$temperature = substr($info, ($start_pos + 2), ($end_pos - $start_pos - 2));

				if(is_numeric($temperature))
				{
					$unit = substr($info, $end_pos + 2, 1);
					if($unit == 'F')
					{
						$temperature = round((($temperature - 32) * 5 / 9), 2);
					}

					$hdd_temperature = $temperature;
				}
			}
		}

		return $hdd_temperature;
	}
	public static function read_xorg_module_version($module)
	{
		$module_version = false;
		if(isset(phodevi::$vfs->xorg_log))
		{
			$xorg_log = phodevi::$vfs->xorg_log;

			// Don't bother parsing the xorg log if too big
			if(!isset($xorg_log[500000]) && ($module_start = strpos($xorg_log, $module)) > 0)
			{
				$xorg_log = substr($xorg_log, $module_start);
				$temp_version = substr($xorg_log, strpos($xorg_log, 'module version =') + 17);
				$temp_version = substr($temp_version, 0, strpos($temp_version, "\n"));

				if(is_numeric(str_replace('.', '', $temp_version)))
				{
					$module_version = $temp_version;
				}
			}
		}

		return $module_version;
	}
	public static function parse_equal_delimited_file($file, $key)
	{
		$return_value = false;

		foreach(explode("\n", pts_file_io::file_get_contents($file)) as $build_line)
		{
			list($descriptor, $value) = pts_strings::trim_explode('=', $build_line);

			if($descriptor == $key)
			{
				$return_value = $value;
				break;
			}
		}

		return $return_value;
	}
	public static function hardware_values_to_remove()
	{
		return array(
		'empty',
		'null',
		'unknown',
		'unknow',
		'system manufacturer',
		'system version',
		'system name',
		'system product name',
		'to be filled by o.e.m.',
		'not applicable',
		'not specified',
		'not available',
		'oem',
		'00',
		'none',
		'1234567890'
		);
	}
	public static function software_glxinfo_version()
	{
		$info = false;
		if(isset(phodevi::$vfs->glxinfo))
		{
			$glxinfo = phodevi::$vfs->glxinfo;
		}
		else if(PTS_IS_CLIENT && pts_client::executable_in_path('fglrxinfo'))
		{
			$glxinfo = shell_exec('fglrxinfo 2> /dev/null');
		}

		foreach(array('OpenGL core profile version string:', 'OpenGL version string:') as $gl_v_string)
		{
			if($info == false && isset($glxinfo) && ($pos = strpos($glxinfo, $gl_v_string)) !== false)
			{
				$info = substr($glxinfo, $pos + strlen($gl_v_string));
				$info = substr($info, 0, strpos($info, "\n"));
				$info = trim(str_replace(array(' Release', '(Core Profile)'), '', $info));

				// The Catalyst Linux Driver now does something stupid for this string like:
				//  1.4 (2.1 (3.3.11005 Compatibility Profile Context))
				if(($pos = strrpos($info, 'Compatibility Profile Context')) !== false && strpos($info, '(') != ($last_p = strrpos($info, '(')))
				{
					if(is_numeric(str_replace(array('(', '.', ' '), '', substr($info, 0, $last_p))))
					{
						// This looks like a stupid Catalyst driver string, so grab the last GL version reported
						$info = str_replace(array('(', ')'), '', substr($info, ($last_p + 1)));
						break;
					}
				}
			}
		}

		return $info;
	}
	public static function software_glxinfo_glsl_version()
	{
		$info = false;
		if(isset(phodevi::$vfs->glxinfo))
		{
			$glxinfo = phodevi::$vfs->glxinfo;
		}
		else if(PTS_IS_CLIENT && pts_client::executable_in_path('fglrxinfo'))
		{
			$glxinfo = shell_exec('fglrxinfo 2> /dev/null');
		}

		foreach(array('OpenGL core profile shading language version string:', 'OpenGL shading language version string:') as $shader_v_string)
		{
			if(isset($glxinfo) && $info == false && ($pos = strpos($glxinfo, $shader_v_string)) !== false)
			{
				$info = substr($glxinfo, $pos + strlen($shader_v_string));
				$info = substr($info, 0, strpos($info, "\n"));
				$info = trim($info);
			}
		}

		return $info;
	}
	public static function software_glxinfo_opengl_extensions()
	{
		$info = false;
		if(isset(phodevi::$vfs->glxinfo))
		{
			$glxinfo = phodevi::$vfs->glxinfo;

			if(($pos = strpos($glxinfo, 'OpenGL extensions:')) !== false)
			{
				$info = substr($glxinfo, $pos + 19);
				$info = substr($info, 0, strpos($info, PHP_EOL . PHP_EOL));
				$info = trim($info);
			}
		}

		return $info;
	}
	public static function glxinfo_read_line($head)
	{
		$info = false;
		if(isset(phodevi::$vfs->glxinfo))
		{
			$glxinfo = phodevi::$vfs->glxinfo;

			$to_find = $head . ':';
			if(($pos = strpos($glxinfo, $to_find)) !== false)
			{
				$info = substr($glxinfo, $pos + strlen($to_find));
				$info = substr($info, 0, strpos($info, PHP_EOL));
				$info = trim($info);
			}
		}

		return $info;
	}
}

?>
