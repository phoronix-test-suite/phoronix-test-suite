<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2018 - 2021, Phoronix Media
	Copyright (C) 2018 - 2021, Michael Larabel

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

class system_properties implements pts_option_interface
{
	const doc_section = 'System';
	const doc_description = 'Display various hardware/software system properties detected by the Phoronix Device Interface (Phodevi) library.';

	public static function run($r)
	{
		$properties = phodevi::read_all_properties();

		foreach($properties as $component => $component_properties)
		{
			echo strtoupper($component) . PHP_EOL;
			foreach($component_properties as $property => $value)
			{
				echo '     ' . $property . ' = ';

				if(is_array($value))
				{
					foreach($value as $si => $sv)
					{
						if(is_array($sv))
						{
							foreach($sv as $ssi => $ssv)
							{
								echo PHP_EOL . '          ' . $ssi . ' = ' . $ssv;
							}
							echo PHP_EOL;
						}
						else
						{
							echo PHP_EOL . '          ' . $sv . ' = ' . $sv . PHP_EOL;
						}
						//echo PHP_EOL;
					}
/*					echo PHP_EOL;
					foreach($value as $i => $j)
						echo '         ' . $i . ' = ' . $j . PHP_EOL; */
				}
				else
				{
					echo $value . PHP_EOL;
				}
			}
			echo PHP_EOL;
		}
	}
}

?>
