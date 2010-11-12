<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2010, Phoronix Media
	Copyright (C) 2009 - 2010, Michael Larabel
	pts_ResultFileTable.php: The result file table object

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

class pts_ResultFileSystemsTable extends pts_Table
{
	public function __construct(&$result_file)
	{
		$columns = $result_file->get_system_identifiers();
		$rows = array();
		$table_data = array();

		foreach($result_file->get_system_hardware() as $info_string)
		{
			$col = array();
			foreach(explode(', ', $info_string) as $component)
			{
				$c_pos = strpos($component, ': ');

				if($c_pos !== false)
				{
					$index = substr($component, 0, $c_pos);
					$value = substr($component, ($c_pos + 2));

					if(isset($rows[$index]) == false)
					{
						$rows[$index] = $index;
					}
					array_push($col, $value);				
				}
			}
			array_push($table_data, $col);
		}

		parent::__construct($rows, $columns, $table_data);
		$this->graph_font_size_identifiers *= 0.8;
		$this->column_heading_vertical = false;
	}
}

?>
