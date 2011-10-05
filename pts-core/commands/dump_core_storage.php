<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

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

class dump_core_storage implements pts_option_interface
{
	public static function run($r)
	{
		pts_client::$display->generic_heading('Core Storage');
		$pso = pts_storage_object::recover_from_file(PTS_CORE_STORAGE);

		foreach($pso->get_objects() as $pso_index => $pso_object)
		{
			if($pso_index != 'phodevi_smart_cache')
			{
				echo $pso_index . ': ';

				if(is_array($pso_object))
				{
					foreach($pso_object as $key => $element)
					{
						echo PHP_EOL . "\t" . $key . ': ' . $element;
					}
				}
				else
				{
					echo $pso_object;
				}

				echo PHP_EOL;
			}
		}

		echo PHP_EOL;
	}
}

?>
