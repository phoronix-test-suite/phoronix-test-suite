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

class pts_gtk_button extends GtkButton
{
	public function __construct($button_text, $on_click, $pass_on_click, $width = -1, $height = -1, $image = null)
	{
		parent::__construct($button_text);
		$this->connect_simple("clicked", $on_click, $pass_on_click);
		$this->set_size_request($width, $height);

		if($image != null)
		{
			$img = GtkImage::new_from_stock($image, Gtk::ICON_SIZE_SMALL_TOOLBAR);
			$this->set_image($img);
		}
	}
}

?>
