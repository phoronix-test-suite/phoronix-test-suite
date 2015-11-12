<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2015, Phoronix Media
	Copyright (C) 2009 - 2015, Michael Larabel

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

class gpu_power extends phodevi_sensor
{
	const SENSOR_TYPE = 'gpu';
	const SENSOR_SENSES = 'power';
	const SENSOR_UNIT = 'Milliwatts';

	public function read_sensor()
	{
		$gpu_power = -1;

		if(is_readable('/sys/kernel/debug/dri/0/i915_emon_status'))
		{
			$i915_emon_status = file_get_contents('/sys/kernel/debug/dri/0/i915_emon_status');
			$power = strpos($i915_emon_status, 'Total power: ');

			if($power !== false)
			{
				$power = substr($i915_emon_status, $power + 13);
				$power = substr($power, 0, strpos($power, PHP_EOL));

				if(is_numeric($power))
				{
					if($power > 10000000)
					{
						$power /= 1000;
					}

					$gpu_power = $power;
				}
			}
		}

		return $gpu_power;
	}

}

?>
