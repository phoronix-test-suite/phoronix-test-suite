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

class pts_test_result_buffer_item
{
	private $result_identifier;
	private $result_final;
	private $result_raw;

	public function __construct($identifier, $final, $raw = null)
	{
		$this->result_identifier = $identifier;
		$this->result_final = $final;
		$this->result_raw = $raw;
	}
	public function get_result_identifier()
	{
		return $this->result_identifier;
	}
	public function get_result_value()
	{
		return $this->result_final;
	}
	public function get_result_raw()
	{
		return $this->result_raw;
	}
}

?>
