<?php

/*
   Copyright (C) 2008, Michael Larabel.
   Copyright (C) 2008, Phoronix Media.

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

if(!extension_loaded("gtk"))
	dl("php_gtk2.so");

require("pts-core/functions/pts-functions.php");
require("pts-core/functions/pts-functions-gtk.php");

$window = new GtkWindow();
$window->set_title("Phoronix Test Suite");
$window->set_border_width(10);
$window->connect_simple("destroy", "gtk_shutdown");
$window->set_size_request(450, -1);

$window->add($vbox = new GtkVBox());

$pts_title = new GtkLabel("Phoronix Test Suite");
$pts_title->modify_font(new PangoFontDescription("FreeSans 21"));
$pts_title->modify_fg(Gtk::STATE_NORMAL, GdkColor::parse("#2b6b29"));
$pts_title->set_size_request(-1, 60);
$vbox->pack_start($pts_title, 0, 0);

$vbox->pack_start($hbox = new GtkHBox(), 0, 0);

// Select Benchmark Row
$hbox->pack_start(new GtkLabel("Benchmark: "), 0, 0);

// Benchmark Selection GtkComboBox
$benchmark_combobox = new GtkComboBox();
$list_model = new GtkListStore(Gtk::TYPE_STRING);
$benchmark_combobox->set_model($list_model);
$cell_renderer = new GtkCellRendererText();
$benchmark_combobox->pack_start($cell_renderer);
$benchmark_combobox->set_attributes($cell_renderer, "text", 0);

// Load Up Benchmark List
foreach(pts_benchmark_names_to_array() as $menu_item)
{
    $list_model->append(array($menu_item));
}

// Display
$hbox->pack_start($benchmark_combobox, 0, 0);
$hbox->pack_start(new GtkLabel(" "), 0, 0);

$submit_button = new GtkButton("Submit");
$submit_button->set_size_request(74, 28);
$submit_button->connect("clicked", "combobox_select", $benchmark_combobox);
$hbox->pack_start($submit_button, 0, 0);

$vbox->pack_start(new GtkLabel(" "), 0, 0);

$pts_desc = new GtkLabel("Select the benchmark you would like to run. The application must be installed prior to running the Phoronix Test Suite. Any requests or issues can be addressed in the Phoronix Forums.");
$pts_desc->set_size_request(430, -1);
$pts_desc->set_line_wrap(true);
$vbox->pack_start($pts_desc, 0, 0);

$vbox->pack_start(new GtkLabel(" "), 0, 0);

$pts_link = new GtkLabel("www.phoronix.com");
$pts_link->modify_font(new PangoFontDescription("FreeSans 14"));
$pts_link->modify_fg(Gtk::STATE_NORMAL, GdkColor::parse("#2b6b29"));
$pts_link->set_size_request(-1, 30);
$vbox->pack_start($pts_link, 0, 0);

$window->show_all();
Gtk::main();
?>
