<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2019, Phoronix Media
	Copyright (C) 2009 - 2019, Michael Larabel
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
		$columns = array();
		$hw = array();
		$sw = array();
		foreach($result_file->get_systems() as $system)
		{
			$columns[] = $system->get_identifier();
			$hw[] = $system->get_hardware();
			$sw[] = $system->get_software();
		}

		$rows = array();
		$table_data = array();

		pts_result_file_analyzer::system_components_to_table($table_data, $columns, $rows, $hw);
		pts_result_file_analyzer::system_components_to_table($table_data, $columns, $rows, $sw);

		pts_result_file_analyzer::compact_result_table_data($table_data, $columns, true); // TODO: see if this true value works fine but if rendering starts messing up, disable it

		if(defined('OPENBENCHMARKING_IDS'))
		{
			foreach($columns as &$column)
			{
				$column = new pts_graph_ir_value($column);
				$column->set_attribute('href', 'https://openbenchmarking.org/system/' . OPENBENCHMARKING_IDS . '/' . $column);
			}
		}

		parent::__construct($rows, $columns, $table_data, $result_file);
		$this->i['identifier_size'] *= 0.8;
		$this->column_heading_vertical = false;
		$this->i['graph_title'] = $result_file->get_title();

		if(!defined('PHOROMATIC_EXPORT_VIEWER'))
			pts_Table::report_system_notes_to_table($result_file, $this);
	}
}

?>
