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

class pts_gtk_window extends GtkWindow
{
	public function __construct($window_title = "Phoronix Test Suite", $window_width = -1, $window_height = -1)
	{
		parent::__construct();
		$this->connect_simple("destroy", array("Gtk", "main_quit"));

		$this->set_title($window_title);
		$this->set_size_request($window_width, $window_height);

		$this->set_icon(GdkPixbuf::new_from_file(MEDIA_DIR . "pts-icon.png"));
	}
}

?>
