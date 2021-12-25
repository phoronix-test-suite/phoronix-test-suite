<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2011, Phoronix Media
	Copyright (C) 2009 - 2011, Michael Larabel

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

class dump_phodevi_smart_cache implements pts_option_interface
{
	const doc_section = 'Debugging';
	const doc_description = 'This option is used for displaying the contents of the Phodevi smart cache on the system.';
	public static function run($r)
	{
		$pso = pts_storage_object::recover_from_file(PTS_CORE_STORAGE);
		$phodevi_sc = $pso->read_object('phodevi_smart_cache');

		foreach($phodevi_sc as $index => $element)
		{
			if($index != 'phodevi_cache')
			{
				echo $index . ': ' . $element . PHP_EOL;
			}
		}

		echo PHP_EOL;
		print_r($phodevi_sc->read_cache());
		echo PHP_EOL;
	}
}

?>
