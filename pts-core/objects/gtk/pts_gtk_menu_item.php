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

class pts_gtk_menu_item
{
	var $title;
	var $to_call;
	var $to_call_arg;
	var $type;
	var $attach_image;
	var $active_default;
	var $attach_to_pts_assignment;

	public function __construct($title, $to_call = null, $type = "STRING", $attach_image = null, $set_active_default = false)
	{
		if(is_array($title) && count($title) == 2 && $type != "RADIO_BUTTON")
		{
			$assignment = $title[0];
			$title = $title[1];
		}
		else
		{
			$assignment = null;
		}

		if($type == "RADIO_BUTTON")
		{
			$title = pts_to_array($title);
		}

		$this->to_call_arg = (count($to_call) > 2 ? array_pop($to_call) : null);
		$this->title = $title;
		$this->to_call = $to_call;
		$this->type = $type;
		$this->attach_image = $attach_image;
		$this->active_default = $set_active_default == true;
		$this->attach_to_pts_assignment = $assignment;
	}
	public function get_title()
	{
		return $this->title;
	}
	public function get_active_default()
	{
		return $this->active_default;
	}
	public function get_function_call()
	{
		return $this->to_call;
	}
	public function get_function_argument()
	{
		return $this->to_call_arg;
	}
	public function get_type()
	{
		return $this->type;
	}
	public function get_image()
	{
		return $this->attach_image;
	}
	public function get_attach_to_pts_assignment()
	{
		return $this->attach_to_pts_assignment;
	}
	public function attach_to_pts_assignment($assignment)
	{
		$this->attach_to_pts_assignment = $assignment;
	}
}

?>
