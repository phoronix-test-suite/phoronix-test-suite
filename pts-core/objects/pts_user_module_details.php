<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008 - 2009, Phoronix Media
	Copyright (C) 2008 - 2009, Michael Larabel

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

class pts_user_module_details
{
	private $module = null;

	public function __construct($module)
	{
		$this->module = $module;
		pts_load_module($module);
	}
	public function get_module_name()
	{
		return pts_module_call($this->module, "module_name");
	}
	public function get_module_version()
	{
		return pts_module_call($this->module, "module_version");
	}
	public function get_module_author()
	{
		return pts_module_call($this->module, "module_author");
	}
	public function get_module_description()
	{
		return pts_module_call($this->module, "module_description");
	}
	public function get_module_info()
	{
		return pts_module_call($this->module, "module_info");
	}
}

?>
