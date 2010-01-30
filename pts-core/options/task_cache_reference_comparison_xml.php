<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2010, Phoronix Media
	Copyright (C) 2010, Michael Larabel

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

class task_cache_reference_comparison_xml implements pts_option_interface
{
	public static function run($r)
	{
		$write_to_system_cache = is_writable("/var/");

		if($write_to_system_cache)
		{
			pts_mkdir("/var/cache/phoronix-test-suite/reference-comparisons/", 0777, true);
		}

		foreach(pts_generic_reference_system_comparison_ids() as $reference_id)
		{
			if(!empty($reference_xml))
			{
				if($write_to_system_cache)
				{
					$reference_xml = pts_global_download_xml($reference_id);
					file_put_contents("/var/cache/phoronix-test-suite/reference-comparisons/" . $reference_id . ".xml", $reference_xml);
				}
				else if(!pts_is_test_result($reference_id))
				{
					pts_clone_from_global($reference_id, false);
				}
			}
		}
	}
}

?>
