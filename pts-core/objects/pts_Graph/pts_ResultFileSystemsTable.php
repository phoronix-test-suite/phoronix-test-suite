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

		$this->component_to_table_data($table_data, $columns, $rows, $result_file->get_system_hardware());
		$this->component_to_table_data($table_data, $columns, $rows, $result_file->get_system_software());

		// Let's try to compact the data
		$c_count = count($table_data);
		$c_index = 0;
		foreach(array_keys($table_data) as $c)
		{
			for($r = 0, $r_count = count($table_data[$c]); $r < $r_count; $r++)
			{
				// Find next-to duplicates
				$match_to = &$table_data[$c][$r];

				if(($match_to instanceof pts_table_value) == false)
				{
					continue;
				}

				$spans = 1;
				for($i = ($c_index + 1); $i < $c_count; $i++)
				{
					$id = $columns[$i];

					if(isset($table_data[$id][$r]) && $match_to == $table_data[$id][$r])
					{
						//echo $match_to . ' ' . $table_data[$i][$r] . ' ' . $i . "\n";
						$spans++;
						$table_data[$id][$r] = null;
					}
					else
					{
						break;
					}
				}

				if($spans > 1)
				{
					$match_to->set_attribute('spans_col', $spans);
				}
			}

			$c_index++;
		}

		parent::__construct($rows, $columns, $table_data, $result_file);
		$this->graph_font_size_identifiers *= 0.8;
		$this->column_heading_vertical = false;
	}
	private function component_to_table_data(&$table_data, &$columns, &$rows, $add_components)
	{
		$col_pos = 0;

		foreach($add_components as $info_string)
		{
			if(!isset($table_data[$columns[$col_pos]]))
			{
				$table_data[$columns[$col_pos]] = array();
			}

			foreach(explode(', ', $info_string) as $component)
			{
				$c_pos = strpos($component, ': ');

				if($c_pos !== false)
				{
					$index = substr($component, 0, $c_pos);
					$value = substr($component, ($c_pos + 2));

					if(($r_i = array_search($index, $rows)) === false)
					{
						array_push($rows, $index);
						$r_i = count($rows) - 1;
					}
					$table_data[$columns[$col_pos]][$r_i] = new pts_table_value($value);				
				}
			}
			$col_pos++;
		}
	}
}

?>
