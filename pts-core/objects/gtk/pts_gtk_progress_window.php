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

class pts_gtk_progress_window extends GtkDialog
{
	var $gtk_progress_bar;
	var $loading_label;

	public function __construct($title)
	{
		parent::__construct($title, null, Gtk::DIALOG_MODAL);
		$this->set_size_request(360, 270);

		$logo = GtkImage::new_from_file(STATIC_DIR . "pts-308x160-t.png");
		$logo->set_size_request(308, 160);

		$this->loading_label = new pts_gtk_label("Loading...");

		$this->gtk_progress_bar = new GtkProgressBar();
		$this->gtk_progress_bar->set_orientation(Gtk::PROGRESS_LEFT_TO_RIGHT);

		pts_gtk_array_to_boxes($this->vbox, array(null, $logo, null, $this->gtk_progress_bar, $this->loading_label), 2, true);

		$this->set_has_separator(false);
		$this->show_all();
	}
	public function update_progress_bar($percent, $label = null)
	{
		$this->gtk_progress_bar->set_fraction($percent / 100);
		$this->gtk_progress_bar->set_text(intval($percent, 0) . "% Complete");

		if($label != null)
		{
			$this->loading_label->set_text($label);
		}

		while(Gtk::events_pending())
		{
			Gtk::main_iteration();
		}
	}
	public function completed()
	{
		$this->destroy();
	}
}

?>
