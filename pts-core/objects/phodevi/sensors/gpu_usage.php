<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2018, Phoronix Media
	Copyright (C) 2009 - 2018, Michael Larabel

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

//TODO test this sensor on different devices

class gpu_usage extends phodevi_sensor
{
	const SENSOR_TYPE = 'gpu';
	const SENSOR_SENSES = 'usage';
	const SENSOR_UNIT = 'Percent';

	private $probe_radeontop = false;
	private $probe_nvidia_smi = false;
	private $probe_nvidia_settings = false;

	function __construct($instance, $parameter)
	{
		parent::__construct($instance, $parameter);

		$this->set_probe_mode();
	}

	public function support_check()
	{
		$test = $this->read_sensor();
		return is_numeric($test) && $test >= 0 && $test <= 100;
	}

	public function read_sensor()
	{
		$gpu_usage = -1;

		if($this->probe_nvidia_settings)
		{
			$gpu_usage = self::read_nvidia_settings_gpu_utilization();
		}
		else if($this->probe_nvidia_smi)
		{
			$gpu_usage = self::nvidia_core_usage();
		}
		else if($this->probe_radeontop)
		{
			$gpu_usage = self::radeontop_gpu_usage();
		}
		else if(is_file('/sys/class/drm/card0/device/gpu_busy_percent'))
		{
			$gpu_usage = pts_file_io::file_get_contents('/sys/class/drm/card0/device/gpu_busy_percent');
		}

		return $gpu_usage;
	}

	private function set_probe_mode()
	{
		if(phodevi::is_mesa_graphics() && pts_client::executable_in_path('radeontop'))
		{
			$this->probe_radeontop = true;
		}
		else if(phodevi::is_nvidia_graphics() || pts_client::executable_in_path('nvidia-smi'))
		{
			$util = $this->read_nvidia_settings_gpu_utilization();

			if($util !== false)
			{
				$this->probe_nvidia_settings = true;
			}
			else if(pts_client::executable_in_path('nvidia-smi'))
			{
				$this->probe_nvidia_smi = true;
			}
		}
	}

	private static function read_nvidia_settings_gpu_utilization()
	{
		$util = phodevi_parser::read_nvidia_extension('GPUUtilization');

		if(is_numeric($util) && $util >= 0 && $util <= 100)
		{
			return $util;
		}
		else
		{
			if(($x = stripos($util, 'graphics=')) !== false)
			{
				$util = substr($util, ($x + 9));
				$util = substr($util, 0, strpos($util, ','));

				if(is_numeric($util) && $util >= 0 && $util <= 100)
				{
					return $util;
				}
			}
		}

		return false;
	}
	private static function nvidia_core_usage()
	{
		$nvidia_smi = shell_exec(escapeshellarg(pts_client::executable_in_path('nvidia-smi')) . ' -a');

		$util = substr($nvidia_smi, strpos($nvidia_smi, 'Utilization'));
		$util = substr($util, stripos($util, 'GPU'));
		$util = substr($util, strpos($util, ':') + 1);
		$util = trim(substr($util, 0, strpos($util, '%')));

		return $util;
	}

	private static function radeontop_gpu_usage()
	{
		$out = shell_exec('radeontop -d - -l 1');

		$pos = strpos($out, 'gpu');
		if($pos === false)
			return -1;

		$out = substr($out, $pos + 4);
		$out = trim(substr($out, 0, strpos($out, '%')));

		return $out;
	}
}

?>
