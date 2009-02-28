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

class pts_gtk_text_area extends GtkScrolledWindow
{
	public function __construct($text, $width = -1, $height = -1)
	{
		parent::__construct();
		$this->set_policy(Gtk::POLICY_AUTOMATIC, Gtk::POLICY_AUTOMATIC);

		$text_view = new GtkTextView();
		$text_buffer = new GtkTextBuffer();
		$text_buffer->set_text($text);
		$text_view->set_buffer($text_buffer);
		$text_view->set_wrap_mode(GTK_WRAP_WORD);
		$text_view->set_size_request($width, $height);

		$this->add($text_view);
	}
}

?>
