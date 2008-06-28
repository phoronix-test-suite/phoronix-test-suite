<?php

if(!extension_loaded("gtk"))
{
	dl("php_gtk2.so");
}

$glade = new GladeXML("pts-core/gui/pts-gui.glade");

$window = $glade->get_widget("pts_main_window");
$window->connect_simple("destroy", array("Gtk", "main_quit"));

$window->show();
Gtk::main();

?>
