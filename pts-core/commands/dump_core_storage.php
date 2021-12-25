<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2014, Phoronix Media
	Copyright (C) 2009 - 2014, Michael Larabel

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
	const doc_skip = true;
	public static function run($r)
	{
		pts_client::$display->generic_heading('Core Storage');
		$pso = pts_storage_object::recover_from_file(PTS_CORE_STORAGE);

		foreach($pso->get_objects() as $pso_index => $pso_object)
		{
			if(!in_array($pso_index, array('global_reported_hw', 'global_reported_sw', 'global_reported_usb', 'global_reported_pci', 'phodevi_smart_cache')))
			{
				self::print_element($pso_index, $pso_object, 0);
			}
		}

		echo PHP_EOL;
	}
	private static function print_element($in, $el, $depth)
	{
		//echo $in . ': ';

		if(is_array($el))
		{
			foreach($el as $key => $element)
			{
				echo PHP_EOL . str_repeat("\t", $depth) . $in . ': ';
				self::print_element($key, $element, ($depth + 1));
			}
		}
		else
		{
			echo PHP_EOL . str_repeat("\t", $depth) . $in . ': ' . $el;
		}
	}
}

?>
