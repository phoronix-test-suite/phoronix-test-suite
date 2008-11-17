<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2008, Phoronix Media
	Copyright (C) 2008, Michael Larabel

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
	var $identifier;
	var $name;
	var $module;
	var $version;
	var $author;
	var $description;
	var $information;

	public function __construct($module_file_path)
	{
		$module = basename(substr($module_file_path, 0, strrpos($module_file_path, ".")));
		$this->module = $module;

		if(!class_exists($module) && substr($module_file_path, -3) == "php")
		{
			include_once($module_file_path);
		}

		$this->name = pts_module_call($module, "module_name");
		$this->version = pts_module_call($module, "module_version");
		$this->author = pts_module_call($module, "module_author");
		$this->description = pts_module_call($module, "module_description");
		$this->information = pts_module_call($module, "module_info");
	}
	public function info_string()
	{
		$str = "";

		$str .= pts_string_header("Module: " . $this->name);

		if(in_array($this->module, pts_attached_modules()))
		{
			$str .= "** This module is currently loaded. **\n";
		}

		$str .= "Version: " . $this->version . "\n";
		$str .= "Author: " . $this->author . "\n";
		$str .= "Description: " . $this->description . "\n";

		if(!empty($this->information))
		{
			$str .= "\n" . $this->information . "\n";
		}

		return $str;
	}
	public function __toString()
	{
		return sprintf("%-22ls - %-30ls [%s]\n", $this->module, $this->name . " v" . $this->version, $this->author);
	}

}

?>
