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

class pts_gtk_advanced_progress_window extends pts_gtk_simple_progress_window
{
	protected $gtk_progress_bar_overall;
	protected $loading_label_overall;
	protected $secondary_label;

	public function __construct($title = null)
	{
		parent::__construct($title);
		$this->set_size_request(500, 380);

		$logo = GtkImage::new_from_file(STATIC_DIR . "images/pts-308x160-t.png");
		$logo->set_size_request(308, 160);

		$this->loading_label = new pts_gtk_label(null);
		$this->loading_label_overall = new pts_gtk_label(null);
		$this->secondary_label = new pts_gtk_label(null);

		$this->gtk_progress_bar_overall = new pts_gtk_progress_bar();

		pts_gtk_array_to_boxes($this->vbox, array(null, $logo, null, $this->gtk_progress_bar, $this->loading_label, null, $this->gtk_progress_bar_overall, $this->loading_label_overall, $this->secondary_label), 2, true);

		$this->set_has_separator(false);
		$this->show_all();
	}
	public function update_progress_bar($percent, $label = null, $percent_overall = -1, $label_overall = null)
	{
		$this->apply_progress_update($this->gtk_progress_bar, $this->loading_label, $percent, $label);
		$this->apply_progress_update($this->gtk_progress_bar_overall, $this->loading_label_overall, $percent_overall, $label_overall);

		while(Gtk::events_pending())
		{
			Gtk::main_iteration();
		}
	}
	public function update_secondary_label($label_string)
	{
		$this->secondary_label->set_text($label_string);
	}
}

?>
