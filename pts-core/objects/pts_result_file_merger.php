<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2015, Phoronix Media
	Copyright (C) 2015, Michael Larabel

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

class pts_result_file_merger
{
	public static function merge($result_merges_to_combine, $pass_attributes = 0)
	{
		if(!is_array($result_merges_to_combine) || empty($result_merges_to_combine))
		{
			return false;
		}

		foreach($result_merges_to_combine as $i => &$merge_select)
		{
			if(!is_file($merge_select->get_result_file()))
			{
				unset($result_merges_to_combine[$i]);
			}
		}

		$merge_to_result_file = new pts_result_file(array_shift($result_merges_to_combine)->get_result_file(), true);

		foreach($result_merges_to_combine as &$merge_select)
		{
			$result_file = new pts_result_file($merge_select->get_result_file(), true);

			if($merge_select->get_rename_identifier())
			{
				$result_file->rename_run_in_result_file(null, $merge_select->get_rename_identifier());
			}

			$merge_to_result_file->add_to_result_file($result_file);
			unset($result_file);
		}
		//$result_file->handle_attributes($pass_attributes);
		return $merge_to_result_file;
	}
}

?>
