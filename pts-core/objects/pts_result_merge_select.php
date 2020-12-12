<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009 - 2020, Phoronix Media
	Copyright (C) 2009 - 2020, Michael Larabel

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

class pts_result_merge_select
{
	private $result_file;
	private $selected_identifiers;
	private $rename_identifier;

	public function __construct($result_file, $selected_identifiers = null, $rename_identifier = null)
	{
		$this->result_file = $result_file;
		$this->selected_identifiers = ($selected_identifiers != null ? pts_arrays::to_array($selected_identifiers) : null);
		$this->rename_identifier = $rename_identifier;
	}
	public function get_result_file()
	{
	/*	if($this->result_file instanceof pts_result_file)
		{
			return $this->result_file->get_file_location();
		}*/

		return $this->result_file;
	}
	public function get_selected_identifiers()
	{
		return $this->selected_identifiers;
	}
	public function __toString()
	{
		return $this->get_result_file() . ':' . $this->get_selected_identifiers();
	}
	public function rename_identifier($new_name)
	{
		// $this->selected_identifers should either contain just the single identifer of what is being renamed or it should be null if being handled through Phoromatic
		$this->rename_identifier = (count($this->selected_identifiers) < 2 ? $new_name : null);
	}
	public function get_rename_identifier()
	{
		return $this->rename_identifier;
	}
	public function set_result_file($f)
	{
		$this->result_file = $f;
	}
}

?>
