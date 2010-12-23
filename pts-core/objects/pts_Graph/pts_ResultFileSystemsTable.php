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

		pts_result_file_analyzer::system_components_to_table($table_data, $columns, $rows, $result_file->get_system_hardware());
		pts_result_file_analyzer::system_components_to_table($table_data, $columns, $rows, $result_file->get_system_software());

		pts_result_file_analyzer::compact_result_table_data($table_data, $columns, true); // TODO: see if this true value works fine but if rendering starts messing up, disable it

		parent::__construct($rows, $columns, $table_data, $result_file);
		$this->graph_font_size_identifiers *= 0.8;
		$this->column_heading_vertical = false;
	}
}

?>
